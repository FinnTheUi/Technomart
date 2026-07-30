<?php

namespace App\Http\Controllers;

use App\Models\Stall;
use App\Models\User;
use App\Models\Contract;
use App\Models\Waitlist;
use App\Models\RentalApplication;
use App\Mail\TenantCredentialsMail;
use App\Traits\ActivityLogTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StallRentalController extends Controller
{
    use ActivityLogTrait;

    /**
     * Public stall dashboard — shows all stalls with prices and statuses.
     */
    public function index()
    {
        $stalls = Stall::withCount(['waitlists' => function ($q) {
            $q->where('status', 'waiting');
        }])->orderBy('name')->get();

        return view('rental.stall-dashboard', compact('stalls'));
    }

    /**
     * Show the rental application form for a specific stall.
     */
    public function apply(Stall $stall)
    {
        if ($stall->status !== 'available') {
            return redirect()->route('stalls.index')->with('error', 'This stall is no longer available.');
        }

        return view('rental.apply', compact('stall'));
    }

    /**
     * Validate and store the rental application, then redirect to checkout.
     */
    public function submitApplication(Request $request, Stall $stall)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:1000',
            'rental_duration' => 'required|integer|min:1|max:12',
            'terms' => 'accepted',
        ], [
            'terms.accepted' => 'You must agree to the Terms and Conditions before proceeding.',
        ]);

        $application = RentalApplication::create([
            'stall_id' => $stall->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'rental_duration' => $request->rental_duration,
            'payment_status' => 'pending',
            'status' => 'pending_approval',
        ]);

        return redirect()->route('rental.checkout', $application);
    }

    /**
     * Show the payment selection screen.
     */
    public function checkout(RentalApplication $application)
    {
        $application->load('stall');
        // Charge only for the first month at checkout instead of full duration
        $totalAmount = $application->stall->price;

        return view('rental.checkout', compact('application', 'totalAmount'));
    }

    /**
     * Simulate GCash payment — create user, contract, send email.
     */
    public function payOnline(RentalApplication $application)
    {
        $application->load('stall');

        // Generate GCash reference
        $gcashRef = 'GCASH-' . strtoupper(Str::random(8));

        // Generate random credentials
        $plainPassword = Str::random(10);
        
        $user = User::where('email', $application->email)->first();
        $isNewUser = false;
        
        if (!$user) {
            // Create tenant user
            $user = User::create([
                'name' => $application->name,
                'email' => $application->email,
                'password' => Hash::make($plainPassword),
                'role' => 'tenant',
            ]);
            $isNewUser = true;
        }

        // Create contract
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addMonths($application->rental_duration);

        Contract::create([
            'tenant_id' => $user->id,
            'stall_id' => $application->stall_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'terms' => 'Rental duration: ' . $application->rental_duration . ' month(s). Payment via GCash.',
            'status' => 'active',
        ]);
        
        // Generate monthly payments
        for ($i = 0; $i < $application->rental_duration; $i++) {
            $dueDate = $startDate->copy()->addMonths($i);
            \App\Models\Payment::create([
                'tenant_id' => $user->id,
                'manager_id' => $application->stall->manager_id,
                'stall_id' => $application->stall_id,
                'amount' => $application->stall->price,
                'due_date' => $dueDate,
                // First month is paid via GCash, subsequent months are pending
                'status' => $i === 0 ? 'paid' : 'pending',
                'paid_date' => $i === 0 ? Carbon::now() : null,
            ]);
        }

        // Mark stall as occupied
        $application->stall->update(['status' => 'occupied']);

        // Update application
        $application->update([
            'payment_method' => 'gcash',
            'payment_status' => 'paid',
            'status' => 'approved',
            'gcash_reference' => $gcashRef,
            'generated_username' => $application->email,
            'generated_password' => $isNewUser ? $plainPassword : null,
        ]);

        // Send credentials email
        Mail::to($application->email)->send(new TenantCredentialsMail($application));

        return redirect()->route('rental.success', $application);
    }

    /**
     * Pay in person — save as pending approval.
     */
    public function payInPerson(RentalApplication $application)
    {
        $application->update([
            'payment_method' => 'in_person',
            'payment_status' => 'pending',
            'status' => 'pending_approval',
        ]);

        return redirect()->route('rental.success', $application);
    }

    /**
     * Show the success confirmation page.
     */
    public function success(RentalApplication $application)
    {
        $application->load('stall');
        return view('rental.success', compact('application'));
    }

    /**
     * Join the waitlist for an occupied stall.
     */
    public function joinWaitlist(Request $request, Stall $stall)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        // Check if already on waitlist
        $existing = Waitlist::where('stall_id', $stall->id)
            ->where('email', $request->email)
            ->where('status', 'waiting')
            ->first();

        if ($existing) {
            return back()->with('error', 'You are already on the waitlist for this stall.');
        }

        Waitlist::create([
            'stall_id' => $stall->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => 'waiting',
        ]);

        return back()->with('success', 'You have been added to the waitlist! We will notify you when this stall becomes available.');
    }
}
