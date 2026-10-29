<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = $this->filteredQuery($request)->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit-log', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(),
            'actions' => ActivityLog::select('action')->distinct()->pluck('action'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'action' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $logs = $this->filteredQuery($request)->oldest()->cursor();

        return response()->streamDownload(function () use ($logs): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date', 'User', 'Email', 'Action', 'Description', 'Subject Type', 'Subject ID']);
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->created_at->toIso8601String(),
                    $log->user?->name ?? 'System',
                    $log->user?->email ?? '',
                    $log->action,
                    $log->description,
                    $log->subject_type,
                    $log->subject_id,
                ]);
            }
            fclose($output);
        }, 'technomart-audit-log-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function filteredQuery(Request $request)
    {
        return ActivityLog::with('user')
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to));
    }
}

