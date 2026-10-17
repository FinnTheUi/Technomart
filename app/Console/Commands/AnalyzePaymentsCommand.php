<?php

namespace App\Console\Commands;

use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Models\Contract;
use App\Models\ElectricBill;
use App\Models\Payment;
use App\Notifications\LeaseExpiringNotification;
use App\Services\Billing\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AnalyzePaymentsCommand extends Command
{
    protected $signature = 'app:analyze-payments';

    protected $description = 'Mark overdue bills (applying late fees), send reminders, and warn about expiring leases';

    public function handle(PaymentService $payments): int
    {
        $this->remindUpcoming();

        // ── Overdue rent and electric bills (after the grace period), with late fees ──
        $cutoff = today()->subDays(config('billing.late_fee.grace_days', 0));
        $overdue = 0;

        foreach ([Payment::class, ElectricBill::class] as $model) {
            $model::with(['tenant', 'stall'])
                ->where('status', PaymentStatus::Pending)
                ->whereDate('due_date', '<', $cutoff)
                ->each(function ($bill) use ($payments, &$overdue) {
                    $payments->markOverdue($bill);
                    $overdue++;
                });
        }

        $this->components->info("Marked {$overdue} bill(s) as overdue.");

        $this->warnExpiringLeases();

        return self::SUCCESS;
    }

    private function remindUpcoming(): void
    {
        Payment::with('tenant')
            ->where('status', PaymentStatus::Pending)
            ->whereBetween('due_date', [today(), today()->addDays(7)])
            ->get()
            ->each(function (Payment $payment) {
                $daysUntilDue = (int) today()->diffInDays($payment->due_date, false);
                $pastOverdue = Payment::where('tenant_id', $payment->tenant_id)->where('status', PaymentStatus::Overdue)->count();

                if ($pastOverdue >= 2 && $daysUntilDue <= 5) {
                    Log::info("URGENT REMINDER (high risk): {$payment->tenant?->name} has PHP {$payment->amount} due in {$daysUntilDue} day(s).");
                } elseif ($daysUntilDue <= 3) {
                    Log::info("Reminder: {$payment->tenant?->name} has PHP {$payment->amount} due in {$daysUntilDue} day(s).");
                }
            });
    }

    /**
     * 30-day lease expiry warning, sent at most once a week per contract.
     */
    private function warnExpiringLeases(): void
    {
        $expiring = Contract::with(['tenant', 'stall'])
            ->where('status', ContractStatus::Active)
            ->whereBetween('end_date', [today(), today()->addDays(30)])
            ->get();

        foreach ($expiring as $contract) {
            if (! $contract->tenant) {
                continue;
            }

            $alreadyNotified = $contract->tenant->notifications()
                ->where('type', LeaseExpiringNotification::class)
                ->where('created_at', '>', now()->subDays(7))
                ->get()
                ->contains(fn ($n) => ($n->data['contract_id'] ?? null) === $contract->id);

            if (! $alreadyNotified) {
                $contract->tenant->notify(new LeaseExpiringNotification($contract));
            }
        }

        $this->components->info("Checked {$expiring->count()} expiring lease(s).");
    }
}
