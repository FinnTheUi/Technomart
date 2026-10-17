<?php

namespace App\Http\Controllers\Manager;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\RentalApplication;
use App\Services\Rental\RentalApplicationApprover;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class RentalApplicationController extends Controller
{
    use ActivityLogTrait;

    public function index(Request $request): View
    {
        return view('manager.rental-applications', [
            'applications' => RentalApplication::with('stall')
                ->whereHas('stall', fn ($q) => $q->where('manager_id', $request->user()->id))
                ->latest()
                ->get(),
        ]);
    }

    public function approve(Request $request, RentalApplication $application, RentalApplicationApprover $approver): RedirectResponse
    {
        $stall = $this->ownStall($request, $application);

        try {
            $emailed = $approver->approve($application, $stall, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->logActivity('approved_application', $request->user()->name.' approved rental application from '.$application->name.' for stall "'.$stall->name.'"', $application);

        if (! $emailed) {
            return back()->with('error', 'Application approved and the tenant account is active, but the approval email to '.$application->email.' could not be sent. Check the mail settings (see storage/logs) and let the applicant know.');
        }

        return back()->with('success', 'Application approved! An approval email was sent to '.$application->email.'.');
    }

    public function reject(Request $request, RentalApplication $application): RedirectResponse
    {
        $this->ownStall($request, $application);

        if (! $application->isPending()) {
            return back()->with('error', 'This application has already been processed.');
        }

        $application->update(['status' => ApplicationStatus::Rejected]);
        $this->logActivity('rejected_application', $request->user()->name.' rejected rental application from '.$application->name, $application);

        return back()->with('success', 'Application rejected.');
    }

    /**
     * Applications for other managers' stalls are reported as not found (not forbidden)
     * so their existence isn't disclosed.
     */
    private function ownStall(Request $request, RentalApplication $application)
    {
        $stall = $application->stall;
        abort_unless($stall && $stall->manager_id === $request->user()->id, 404);

        return $stall;
    }
}
