<?php

namespace App\Http\Controllers\Manager;

use App\Enums\MaintenanceStatus;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Notifications\SystemEventNotification;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    use ActivityLogTrait;

    public function index(Request $request): View
    {
        return view('manager.inbox', [
            'requests' => MaintenanceRequest::with(['tenant', 'stall'])
                ->whereHas('stall', fn ($q) => $q->where('manager_id', $request->user()->id))
                ->latest()
                ->paginate(10)
                ->withQueryString()
                ->fragment('request-records'),
        ]);
    }

    public function updateStatus(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        Gate::authorize('update', $maintenanceRequest);
        $request->validate(['status' => ['required', Rule::enum(MaintenanceStatus::class)]]);

        $oldStatus = $maintenanceRequest->status;
        $maintenanceRequest->update(['status' => $request->status]);

        $this->logActivity('updated_maintenance', $request->user()->name.' changed maintenance request "'.$maintenanceRequest->subject.'" from '.$oldStatus->value.' to '.$request->status, $maintenanceRequest);

        if ($oldStatus !== $maintenanceRequest->status) {
            $maintenanceRequest->tenant?->notify(new SystemEventNotification(
                'maintenance_updated', 'Your maintenance request "'.$maintenanceRequest->subject.'" is now '.$maintenanceRequest->status->value.'.',
                route('tenant.maintenance.detail', $maintenanceRequest),
            ));
        }

        return back()->with('success', 'Request status updated.');
    }
}
