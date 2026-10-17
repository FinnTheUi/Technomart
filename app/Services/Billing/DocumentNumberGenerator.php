<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\DB;

/**
 * Gap-free, per-year sequential document numbers such as OR-2026-000123 or PO-2026-0004.
 * Safe under concurrency: the sequence row is locked for the duration of the transaction.
 */
class DocumentNumberGenerator
{
    public function next(string $prefix, int $padding = 6): string
    {
        $year = now()->year;
        $key = "{$prefix}-{$year}";

        $number = DB::transaction(function () use ($key) {
            DB::table('document_sequences')->insertOrIgnore([
                'key' => $key,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $current = DB::table('document_sequences')->where('key', $key)->lockForUpdate()->value('last_number');
            $next = $current + 1;

            DB::table('document_sequences')->where('key', $key)->update(['last_number' => $next, 'updated_at' => now()]);

            return $next;
        });

        return sprintf('%s-%04d-%0'.$padding.'d', $prefix, $year, $number);
    }

    public function nextReceiptNumber(): string
    {
        return $this->next(config('billing.receipt_prefix', 'OR'));
    }
}
