<?php

namespace App\Http\Controllers\Manager;

use App\Enums\StallStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\AssignTenantRequest;
use App\Http\Requests\Manager\UpdateStallRequest;
use App\Models\Location;
use App\Models\Stall;
use App\Models\User;
use App\Services\Rental\LeaseService;
use App\Services\Rental\StallLayoutService;
use App\Services\Rental\WaitlistService;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Stalls are generated from the floor layout (config/stalls.php); managers set prices,
 * details, maintenance status and tenants.
 */
class StallController extends Controller
{
    use ActivityLogTrait;

    public function index(Request $request, StallLayoutService $layout): View
    {
        $layout->sync($request->user());
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:50'],
        ]);
        $search = trim((string) ($filters['q'] ?? ''));

        $stallPage = Stall::with('activeContract.tenant')
            ->where('manager_id', $request->user()->id)
            ->searchName($search)
            ->orderBy('floor_number')
            ->orderBy('stall_number')
            ->paginate(10)
            ->withQueryString();

        return view('manager.stalls', [
            'stalls' => $stallPage->getCollection()->groupBy('floor_number'),
            'stallPage' => $stallPage,
            'search' => $search,
        ]);
    }

    public function edit(Stall $stall): View
    {
        Gate::authorize('update', $stall);

        return view('manager.stall-form', [
            'stall' => $stall->load('activeContract.tenant'),
            'locations' => Location::orderBy('name')->get(),
            'tenants' => User::where('role', User::ROLE_TENANT)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateStallRequest $request, Stall $stall): RedirectResponse
    {
        Gate::authorize('update', $stall);

        $oldPrice = (float) $stall->price;
        $stall->update($request->validated());

        $this->logActivity('updated_stall', $request->user()->name.' updated "'.$stall->name.'"'
            .($oldPrice !== (float) $stall->price ? ' (price PHP '.number_format($oldPrice, 2).' → PHP '.number_format($stall->price, 2).')' : ''), $stall);

        return redirect()->route('manager.stalls')->with('success', '"'.$stall->name.'" updated. New prices apply to leases created from now on.');
    }

    public function assignTenant(AssignTenantRequest $request, Stall $stall, LeaseService $leases): RedirectResponse
    {
        Gate::authorize('update', $stall);

        try {
            $leases->assignTenant($stall, User::findOrFail($request->tenant_id), $request->integer('months'), $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('manager.stalls')->with('success', 'Tenant assigned to "'.$stall->name.'". A lease and monthly rent bills were created.');
    }

    /**
     * Maintenance on/off. "Occupied" is only valid with an active lease and "available" only without one,
     * so the public map always reflects reality.
     */
    public function updateStatus(Request $request, Stall $stall, WaitlistService $waitlist): RedirectResponse
    {
        Gate::authorize('update', $stall);
        $request->validate(['status' => ['required', Rule::enum(StallStatus::class)]]);

        $newStatus = StallStatus::from($request->status);
        $oldStatus = $stall->status;
        $leased = $stall->hasActiveLease();

        if ($newStatus === StallStatus::Available && $leased) {
            return back()->with('error', 'This stall has an active lease. Terminate the lease first (Leases page).');
        }

        if ($newStatus === StallStatus::Occupied && ! $leased) {
            return back()->with('error', 'Assign a tenant to mark this stall as occupied.');
        }

        $stall->update(['status' => $newStatus]);

        $this->logActivity('updated_stall_status', $request->user()->name.' changed stall "'.$stall->name.'" from '.$oldStatus->value.' to '.$newStatus->value, $stall);

        if ($newStatus === StallStatus::Available && $oldStatus !== StallStatus::Available) {
            $waitlist->notifyStallAvailable($stall);
        }

        return back()->with('success', 'Stall status updated.');
    }

    public function floorPlan(Request $request, StallLayoutService $layout): View
    {
        $layout->sync($request->user());

        $managerStalls = Stall::where('manager_id', $request->user()->id);
        $stalls = (clone $managerStalls)->orderBy('stall_number')->get();
        $directoryStalls = (clone $managerStalls)
            ->orderBy('floor_number')
            ->orderBy('stall_number')
            ->paginate(10, ['*'], 'directory_page')
            ->withQueryString()
            ->fragment('stall-directory');

        return view('manager.floor-plan', [
            'directoryStalls' => $directoryStalls,
            'floor1Stalls' => $stalls->where('floor_number', 1)->keyBy('stall_number'),
            'floor2Stalls' => $stalls->where('floor_number', 2)->keyBy('stall_number'),
        ]);
    }

    public function updatePosition(Request $request, Stall $stall): JsonResponse
    {
        Gate::authorize('update', $stall);

        $stall->update($request->validate([
            'floor_x' => 'required|integer|min:0',
            'floor_y' => 'required|integer|min:0',
        ]));

        return response()->json(['success' => true]);
    }
}
