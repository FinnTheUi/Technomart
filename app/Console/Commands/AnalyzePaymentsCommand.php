<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Payment;
use App\Notifications\LeaseExpiringNotification;
use App\Notifications\OverduePaymentNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AnalyzePaymentsCommand extends Command
{
    protected $signature = 'app:analyze-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze pending payments, send overdue notifications, and check expiring leases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting payment analysis...');

        // ── 1. Upcoming Payment Reminders ──
        $upcomingPayments = Payment::where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->get();

        foreach ($upcomingPayments as $payment) {
            $tenant = $payment->tenant;

            // Simple Predictive Heuristic: Count past overdue payments
            $pastOverdueCount = Payment::where('tenant_id', $tenant->id)
                ->where('status', 'overdue')
                ->count();

            // High risk threshold
            $isHighRisk = $pastOverdueCount >= 2;

            $daysUntilDue = now()->diffInDays(Carbon::parse($payment->due_date), false);

            if ($isHighRisk && $daysUntilDue <= 5 && $daysUntilDue >= 0) {
                Log::info("URGENT REMINDER (High Risk): Tenant {$tenant->name} has a payment of {$payment->amount} due in {$daysUntilDue} days.");
            } elseif ($daysUntilDue <= 3 && $daysUntilDue >= 0) {
                Log::info("Standard Reminder: Tenant {$tenant->name} has a payment of {$payment->amount} due in {$daysUntilDue} days.");
            }
        }

        // ── 2. Auto-mark Overdue & Send Notifications ──
        $newlyOverdue = Payment::where('status', 'pending')
            ->where('due_date', '<', now())
            ->get();

        foreach ($newlyOverdue as $payment) {
            $payment->update(['status' => 'overdue']);

            if ($payment->tenant) {
                $payment->tenant->notify(new OverduePaymentNotification($payment));
                $this->line("  → Sent overdue notification to {$payment->tenant->name} for ₱" . number_format($payment->amount, 2));
            }
        }

        $this->info("Marked {$newlyOverdue->count()} payment(s) as overdue.");

        // ── 3. Lease Expiration Warnings (30-day window) ──
        $expiringContracts = Contract::with(['tenant', 'stall'])
            ->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(30)])
            ->get();

        foreach ($expiringContracts as $contract) {
            if ($contract->tenant) {
                // Only notify once — check if we already sent this notification recently
                $alreadyNotified = $contract->tenant->notifications()
                    ->where('type', LeaseExpiringNotification::class)
                    ->where('created_at', '>', now()->subDays(7))
                    ->whereRaw("JSON_EXTRACT(data, '$.contract_id') = ?", [$contract->id])
                    ->exists();

                if (!$alreadyNotified) {
                    $contract->tenant->notify(new LeaseExpiringNotification($contract));
                    $this->line("  → Sent lease expiring notification to {$contract->tenant->name} for stall \"{$contract->stall->name}\"");
                }
            }
        }

        $this->info("Checked {$expiringContracts->count()} expiring contract(s).");
        $this->info('Payment analysis completed successfully.');
    }
}
