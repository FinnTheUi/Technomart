<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports');
    }

    public function download(Request $request, string $report): StreamedResponse
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        [$headers, $rows] = match ($report) {
            'rent' => $this->rent($filters),
            'orders' => $this->orders($filters),
            'inventory' => $this->inventory($filters),
        };

        return response()->streamDownload(function () use ($headers, $rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, "technomart-{$report}-".now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function rent(array $filters): array
    {
        $rows = Payment::with(['tenant', 'manager', 'stall'])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('due_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('due_date', '<=', $date))
            ->orderBy('due_date')->cursor()->map(fn (Payment $payment) => [
                $payment->id, $payment->tenant?->name, $payment->stall?->name, $payment->manager?->name,
                $payment->due_date?->toDateString(), $payment->amount, $payment->late_fee,
                $payment->status->value, $payment->paid_date?->toDateString(), $payment->receipt_number,
            ]);

        return [['ID', 'Tenant', 'Stall', 'Manager', 'Due Date', 'Amount', 'Late Fee', 'Status', 'Paid Date', 'Receipt'], $rows];
    }

    private function orders(array $filters): array
    {
        $rows = Order::with(['user', 'product'])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->oldest()->cursor()->map(fn (Order $order) => [
                $order->id, $order->created_at->toDateString(), $order->user?->name, $order->product?->name,
                $order->quantity, $order->total_price, $order->status->value, $order->paid_at?->toDateString(), $order->receipt_number,
            ]);

        return [['ID', 'Date', 'Tenant', 'Product', 'Quantity', 'Total', 'Status', 'Paid Date', 'Receipt'], $rows];
    }

    private function inventory(array $filters): array
    {
        $rows = StockMovement::with(['product', 'user'])
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->oldest()->cursor()->map(fn (StockMovement $movement) => [
                $movement->id, $movement->created_at->toIso8601String(), $movement->product?->name,
                $movement->type->value, $movement->quantity, $movement->balance_after, $movement->user?->name, $movement->note,
            ]);

        return [['ID', 'Date', 'Product', 'Movement', 'Quantity', 'Balance After', 'Recorded By', 'Note'], $rows];
    }
}

