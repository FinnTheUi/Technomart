<?php

namespace App\Services\Analytics;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\TenantRiskScore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Scores each tenant's likelihood of paying late, per manager.
 *
 * Primary: the logistic-regression model in ml/ (predict_risk.py, trained by `ml:train`).
 * Fallback: the tenant's historical late ratio, used when Python or the model is unavailable.
 *
 * Scores are computed daily by `ml:score` and cached in tenant_risk_scores, so dashboards
 * never wait on Python. Each score carries plain-language reasons ("factors").
 *
 * A bill counts as "late" if it is overdue, or was paid after its due date.
 */
class TenantRiskAnalyzer
{
    public const HIGH_THRESHOLD = 50;

    public const MEDIUM_THRESHOLD = 25;

    /** How many recent settled bills the "x of the last n" factor looks at. */
    private const RECENT_WINDOW = 6;

    /**
     * Cached scores for a manager's tenants, highest risk first. Computes them on first use.
     *
     * @return Collection<int, object>
     */
    public function forManager(int $managerId, int $limit = 10): Collection
    {
        if (! TenantRiskScore::where('manager_id', $managerId)->exists()) {
            $this->refreshForManager($managerId);
        }

        return TenantRiskScore::with('tenant')
            ->where('manager_id', $managerId)
            ->orderByDesc('score')
            ->limit($limit)
            ->get()
            ->map(fn (TenantRiskScore $row) => (object) [
                'id' => $row->tenant_id,
                'name' => $row->tenant->name ?? 'Unknown',
                'email' => $row->tenant->email ?? '',
                'risk_score' => $row->score,
                'risk_level' => $row->level,
                'source' => $row->source,
                'factors' => $row->factors ?? [],
                'computed_at' => $row->computed_at,
            ]);
    }

    /**
     * Recompute and store scores for every manager with billing history. Returns rows written.
     */
    public function refreshAll(): int
    {
        return Payment::distinct()->pluck('manager_id')->sum(fn ($id) => $this->refreshForManager((int) $id));
    }

    public function refreshForManager(int $managerId): int
    {
        $tenants = $this->history($managerId);
        $predictions = $tenants->isEmpty() ? null : $this->predict($tenants);
        $now = now();

        DB::transaction(function () use ($managerId, $tenants, $predictions, $now) {
            TenantRiskScore::where('manager_id', $managerId)->whereNotIn('tenant_id', $tenants->pluck('id'))->delete();

            foreach ($tenants as $tenant) {
                $fromModel = $predictions !== null && isset($predictions[$tenant->id]);
                $score = $fromModel
                    ? (int) round($predictions[$tenant->id] * 100)
                    : $this->historicalScore($tenant);

                TenantRiskScore::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'manager_id' => $managerId],
                    [
                        'score' => max(0, min(100, $score)),
                        'level' => $this->level($score),
                        'source' => $fromModel ? 'model' : 'history',
                        'factors' => $tenant->factors,
                        'computed_at' => $now,
                    ],
                );
            }
        });

        return $tenants->count();
    }

    public function level(int $score): string
    {
        return match (true) {
            $score >= self::HIGH_THRESHOLD => 'High',
            $score >= self::MEDIUM_THRESHOLD => 'Medium',
            default => 'Low',
        };
    }

    /**
     * Whether a bill counts as late (overdue, or paid after its due date).
     */
    public static function isLate(Payment $bill): bool
    {
        return $bill->status === PaymentStatus::Overdue
            || ($bill->status === PaymentStatus::Paid && $bill->paid_date && $bill->paid_date->gt($bill->due_date->copy()->endOfDay()));
    }

    /**
     * Training rows for the model: for every settled bill, the tenant's history *before*
     * that bill (features) and whether that bill was late (label). Chronological per tenant,
     * so the model never sees the future.
     *
     * @return Collection<int, array{past_paid:int, past_overdue:int, overdue_ratio:float, amount:float, is_overdue:int}>
     */
    public function trainingRows(): Collection
    {
        $rows = collect();

        Payment::whereIn('status', [PaymentStatus::Paid, PaymentStatus::Overdue])
            ->orderBy('tenant_id')
            ->orderBy('due_date')
            ->get()
            ->groupBy('tenant_id')
            ->each(function (Collection $bills) use ($rows) {
                $paid = 0;
                $late = 0;

                foreach ($bills as $bill) {
                    $settled = $paid + $late;
                    $isLate = self::isLate($bill);

                    $rows->push([
                        'past_paid' => $paid,
                        'past_overdue' => $late,
                        'overdue_ratio' => $settled > 0 ? round($late / $settled, 4) : 0.0,
                        'amount' => (float) $bill->amount,
                        'is_overdue' => $isLate ? 1 : 0,
                    ]);

                    $isLate ? $late++ : $paid++;
                }
            });

        return $rows;
    }

    /**
     * Per-tenant features and reasons from the manager's settled (paid/overdue) bills.
     *
     * @return Collection<int, object>
     */
    private function history(int $managerId): Collection
    {
        return Payment::with('tenant:id,name,email')
            ->where('manager_id', $managerId)
            ->whereIn('status', [PaymentStatus::Paid, PaymentStatus::Overdue])
            ->orderBy('due_date')
            ->get()
            ->groupBy('tenant_id')
            ->map(function (Collection $bills, $tenantId) {
                $late = $bills->filter(fn (Payment $b) => self::isLate($b));
                $onTime = $bills->count() - $late->count();

                return (object) [
                    'id' => (int) $tenantId,
                    'past_paid' => $onTime,
                    'past_overdue' => $late->count(),
                    'overdue_ratio' => $bills->count() > 0 ? $late->count() / $bills->count() : 0,
                    'amount' => (float) $bills->avg('amount'),
                    'total' => $bills->count(),
                    'factors' => $this->factors($bills),
                ];
            })
            ->values();
    }

    /**
     * Plain-language reasons shown to managers next to the score.
     *
     * @param  Collection<int, Payment>  $bills  ordered by due date
     * @return array<int, string>
     */
    private function factors(Collection $bills): array
    {
        $factors = [];

        $recent = $bills->slice(-self::RECENT_WINDOW);
        $recentLate = $recent->filter(fn (Payment $b) => self::isLate($b))->count();
        if ($recentLate > 0) {
            $factors[] = "{$recentLate} of the last {$recent->count()} bills were late";
        }

        $currentlyOverdue = $bills->where('status', PaymentStatus::Overdue)->count();
        if ($currentlyOverdue > 0) {
            $factors[] = "{$currentlyOverdue} bill(s) currently overdue";
        }

        $daysLate = $bills
            ->filter(fn (Payment $b) => $b->status === PaymentStatus::Paid && $b->paid_date && self::isLate($b))
            ->map(fn (Payment $b) => $b->due_date->diffInDays($b->paid_date));
        if ($daysLate->isNotEmpty()) {
            $factors[] = 'Pays about '.(int) round($daysLate->avg()).' day(s) late on average when late';
        }

        return $factors ?: ['No late payments on record ('.$bills->count().' bills)'];
    }

    /**
     * @return array<int, float>|null tenant id => probability (0..1), or null if the model could not run
     */
    private function predict(Collection $tenants): ?array
    {
        $features = $tenants->map(fn ($t) => [
            'id' => $t->id,
            'past_paid' => $t->past_paid,
            'past_overdue' => $t->past_overdue,
            'overdue_ratio' => $t->overdue_ratio,
            'amount' => $t->amount,
        ])->values()->toJson();

        try {
            $process = new Process(
                [config('services.ml.python'), base_path('ml/predict_risk.py'), $features],
                base_path(),
                null,
                null,
                config('services.ml.timeout'),
            );
            $process->run();

            if (! $process->isSuccessful()) {
                Log::warning('Risk model failed; using historical fallback.', ['error' => $process->getErrorOutput()]);

                return null;
            }

            $output = json_decode($process->getOutput(), true);

            if (! is_array($output) || $output === []) {
                return null;
            }

            return collect($output)->mapWithKeys(fn ($row) => [(int) $row['id'] => (float) $row['risk_probability']])->all();
        } catch (Throwable $e) {
            Log::warning('Risk model unavailable; using historical fallback.', ['exception' => $e->getMessage()]);

            return null;
        }
    }

    private function historicalScore(object $tenant): int
    {
        return $tenant->total > 0 ? (int) round($tenant->overdue_ratio * 100) : 0;
    }
}
