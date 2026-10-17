<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\StallStatus;
use App\Http\Requests\Billing\SubmitPaymentRequest;
use App\Http\Requests\Rental\JoinWaitlistRequest;
use App\Http\Requests\Rental\StoreRentalApplicationRequest;
use App\Models\RentalApplication;
use App\Models\Stall;
use App\Models\Waitlist;
use App\Services\Billing\OnlinePaymentService;
use App\Services\Billing\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Public (guest) stall rental flow: browse → apply → checkout → success, plus waitlist.
 *
 * Checkout/payment/success pages are reachable only through expiring signed URLs
 * (see RentalApplication::signedUrl) so one applicant cannot view or alter another's application.
 *
 * Payment options for the first month:
 *  - GCash reference number, checked by the manager on approval
 *  - Online checkout via PayMongo (confirmed by webhook), when configured
 *  - In person at the office
 */
class StallRentalController extends Controller
{
    public function __construct(private readonly OnlinePaymentService $online) {}

    /**
     * Public stall dashboard — shows all stalls with prices and statuses.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:50'],
            'floor' => ['nullable', Rule::in(array_keys(config('stalls.floors')))],
            'status' => ['nullable', Rule::enum(StallStatus::class)],
        ]);

        $search = trim($filters['q'] ?? '');

        $stalls = Stall::withCount(['waitlists' => fn ($q) => $q->where('status', 'waiting')])
            ->searchName($search)
            ->when($filters['floor'] ?? null, fn ($q, $floor) => $q->where('floor_number', $floor))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('floor_number')
            ->orderBy('stall_number')
            ->paginate(6)
            ->withQueryString()
            ->fragment('stall-list');

        // All stalls, grouped by floor, for the visual floor plan map
        $allStalls = Stall::orderBy('stall_number')->get();

        return view('rental.stall-dashboard', [
            'stalls' => $stalls,
            'filters' => ['q' => $search, 'floor' => $filters['floor'] ?? null, 'status' => $filters['status'] ?? null],
            'floor1Stalls' => $allStalls->where('floor_number', 1)->keyBy('stall_number'),
            'floor2Stalls' => $allStalls->where('floor_number', 2)->keyBy('stall_number'),
        ]);
    }

    public function apply(Stall $stall): View|RedirectResponse
    {
        if (! $stall->isAvailable()) {
            return redirect()->route('stalls.index')->with('error', 'This stall is no longer available.');
        }

        return view('rental.apply', compact('stall'));
    }

    public function submitApplication(StoreRentalApplicationRequest $request, Stall $stall): RedirectResponse
    {
        if (! $stall->isAvailable()) {
            return redirect()->route('stalls.index')->with('error', 'This stall is no longer available.');
        }

        $application = RentalApplication::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'address', 'rental_duration']),
            'stall_id' => $stall->id,
            'password_hash' => Hash::make($request->password),
            'payment_status' => PaymentStatus::Pending,
        ]);

        return redirect()->to($application->signedUrl('rental.checkout'));
    }

    public function checkout(RentalApplication $application): View|RedirectResponse
    {
        if (! $application->awaitingPayment()) {
            return redirect()->to($application->signedUrl('rental.success'));
        }

        $application->load('stall');

        return view('rental.checkout', [
            'application' => $application,
            // Only the first month is charged at checkout; the rest are billed monthly after approval.
            'totalAmount' => $application->stall->price,
            'onlinePayments' => $this->online->isEnabled(),
            'gcash' => config('billing.gcash'),
        ]);
    }

    /**
     * The applicant sent the first month via GCash and enters the reference number;
     * the manager checks it before approving.
     */
    public function payOnline(SubmitPaymentRequest $request, RentalApplication $application): RedirectResponse
    {
        if ($application->awaitingPayment()) {
            $application->update([
                'payment_method' => PaymentService::METHOD_GCASH,
                'payment_status' => PaymentStatus::ForVerification,
                'gcash_reference' => $request->reference_number,
            ]);
        }

        return redirect()->to($application->signedUrl('rental.success'));
    }

    /**
     * Pay through PayMongo's hosted checkout (GCash / Maya / card).
     */
    public function payGateway(RentalApplication $application): RedirectResponse
    {
        if (! $this->online->isEnabled()) {
            return back()->with('error', 'Online payment is not available right now.');
        }

        try {
            $url = $this->online->checkoutForApplication(
                $application,
                $application->signedUrl('rental.success'),
                $application->signedUrl('rental.checkout'),
            );
        } catch (RuntimeException $e) {
            return redirect()->to($application->signedUrl('rental.success'))->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    /**
     * Pay in person — the application is held pending manager approval.
     */
    public function payInPerson(RentalApplication $application): RedirectResponse
    {
        if ($application->awaitingPayment()) {
            $application->update(['payment_method' => 'in_person']);
        }

        return redirect()->to($application->signedUrl('rental.success'));
    }

    public function success(RentalApplication $application): View
    {
        return view('rental.success', ['application' => $application->load('stall')]);
    }

    public function joinWaitlist(JoinWaitlistRequest $request, Stall $stall): RedirectResponse
    {
        $alreadyWaiting = Waitlist::where('stall_id', $stall->id)
            ->where('email', $request->email)
            ->where('status', 'waiting')
            ->exists();

        if ($alreadyWaiting) {
            return back()->with('error', 'You are already on the waitlist for this stall.');
        }

        Waitlist::create([
            ...$request->validated(),
            'stall_id' => $stall->id,
            'status' => 'waiting',
        ]);

        return back()->with('success', 'You have been added to the waitlist! We will notify you when this stall becomes available.');
    }
}
