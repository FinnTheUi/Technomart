<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\ContractStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreMaintenanceRequest;
use App\Models\ActivityLog;
use App\Models\MaintenanceRequest;
use App\Models\Stall;
use App\Notifications\SystemEventNotification;
use App\Services\Uploads\ImageSanitizer;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    use ActivityLogTrait;

    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        // Maintenance requests are limited to stalls the tenant currently leases.
        $stalls = Stall::whereHas('contracts', fn ($query) => $query
            ->where('tenant_id', $userId)
            ->where('status', ContractStatus::Active))
            ->get();

        return view('tenant.maintenance', [
            'requests' => MaintenanceRequest::with('stall')
                ->where('tenant_id', $userId)
                ->latest()
                ->paginate(10)
                ->withQueryString()
                ->fragment('request-records'),
            'stalls' => $stalls,
        ]);
    }

    public function store(StoreMaintenanceRequest $request, ImageSanitizer $images): RedirectResponse
    {
        $maintenanceRequest = MaintenanceRequest::create([
            ...$request->safe()->except('image'),
            'tenant_id' => $request->user()->id,
            'image_path' => $request->file('image') ? $images->store($request->file('image'), 'maintenance') : null,
        ]);

        $this->logActivity('submitted_maintenance', $request->user()->name.' submitted maintenance request: "'.$maintenanceRequest->subject.'"', $maintenanceRequest);

        $maintenanceRequest->stall?->manager?->notify(new SystemEventNotification(
            'maintenance_submitted', $request->user()->name.' submitted maintenance request: '.$maintenanceRequest->subject, route('manager.inbox'),
        ));

        return back()->with('success', 'Maintenance request submitted! The manager will be notified.');
    }

    /** Timeline view of one request. */
    public function show(MaintenanceRequest $maintenanceRequest): View
    {
        Gate::authorize('view', $maintenanceRequest);

        return view('tenant.maintenance-detail', [
            'maintenanceRequest' => $maintenanceRequest->load('stall'),
            'timeline' => ActivityLog::where('subject_type', MaintenanceRequest::class)
                ->where('subject_id', $maintenanceRequest->id)
                ->orderBy('created_at')
                ->paginate(20, ['*'], 'timeline_page')->withQueryString(),
        ]);
    }
}
