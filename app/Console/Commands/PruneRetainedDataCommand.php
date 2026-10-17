<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\ElectricBill;
use App\Models\Message;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PruneRetainedDataCommand extends Command
{
    protected $signature = 'data:prune {--execute : Permanently delete data beyond the configured retention periods}';

    protected $description = 'Preview or execute privacy retention rules';

    public function handle(): int
    {
        $queries = [
            'audit logs' => ActivityLog::where('created_at', '<', now()->subDays(config('retention.audit_days'))),
            'messages' => Message::where('created_at', '<', now()->subDays(config('retention.message_days'))),
            'read notifications' => DB::table('notifications')->whereNotNull('read_at')->where('read_at', '<', now()->subDays(config('retention.read_notification_days'))),
        ];

        foreach ($queries as $label => $query) {
            $count = (clone $query)->count();
            $this->line(sprintf('%s: %d eligible', ucfirst($label), $count));
            if ($this->option('execute')) {
                $query->delete();
            }
        }

        $proofCutoff = now()->subDays(config('retention.payment_proof_days'));
        $proofs = Payment::whereNotNull('proof_path')->where('updated_at', '<', $proofCutoff)->get()
            ->concat(ElectricBill::whereNotNull('proof_path')->where('updated_at', '<', $proofCutoff)->get());
        $this->line('Payment proofs: '.$proofs->count().' eligible');

        if ($this->option('execute')) {
            foreach ($proofs as $payable) {
                Storage::disk('local')->delete($payable->proof_path);
                $payable->forceFill(['proof_path' => null])->save();
            }
        }

        $this->components->info($this->option('execute') ? 'Retention rules executed.' : 'Preview only; pass --execute to delete eligible data.');

        return self::SUCCESS;
    }
}
