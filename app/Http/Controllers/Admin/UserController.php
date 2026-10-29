<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreManagerRequest;
use App\Models\User;
use App\Services\Admin\UserAccountService;
use App\Services\Rental\StallLayoutService;
use App\Support\SafeMail;
use App\Traits\ActivityLogTrait;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class UserController extends Controller
{
    use ActivityLogTrait;

    public function __construct(private readonly UserAccountService $accounts) {}

    public function index(): View
    {
        return view('admin.users', [
            'users' => User::orderBy('role')->orderBy('name')->paginate(10)->withQueryString(),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $request->validate(['role' => ['required', Rule::in(User::ROLES)]]);

        $oldRole = $user->role;
        $user->update(['role' => $request->role]);

        $this->logActivity('updated_role', $request->user()->name.' changed '.$user->name.'\'s role from '.$oldRole.' to '.$request->role, $user, ['old_role' => $oldRole, 'new_role' => $request->role]);

        return back()->with('success', $user->name.'\'s role updated to '.$request->role);
    }

    /**
     * Accounts with billing/lease/order history are deactivated instead of deleted.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        try {
            $outcome = $this->accounts->remove($user, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $outcome === UserAccountService::DELETED
            ? 'User deleted successfully.'
            : $user->name.' has billing or lease history, so the account was deactivated instead of deleted. Records are kept for auditing.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        try {
            $user->is_active
                ? $this->accounts->deactivate($user, $request->user())
                : $this->accounts->activate($user, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $user->name.' is now '.($user->fresh()->is_active ? 'active' : 'inactive').'.');
    }

    public function resendVerification(Request $request, User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return back()->with('error', $user->name.' has already verified their email.');
        }

        if (! $user->is_active) {
            return back()->with('error', 'Activate this account before resending verification.');
        }

        $sent = SafeMail::attempt(fn () => $user->sendEmailVerificationNotification(), ['user' => $user->id]);

        $this->logActivity('resent_verification', $request->user()->name.' resent verification to '.$user->email, $user);

        return back()->with($sent ? 'success' : 'error', $sent
            ? 'Verification email sent to '.$user->email.'.'
            : SafeMail::FAILURE_MESSAGE);
    }

    public function invalidateSessions(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Use Log Out to end your current session.');
        }

        $deleted = DB::table('sessions')->where('user_id', $user->id)->delete();
        $this->logActivity('invalidated_sessions', $request->user()->name.' invalidated '.$user->name.'\'s sessions', $user, ['sessions' => $deleted]);

        return back()->with('success', "Ended {$deleted} active session(s) for {$user->name}.");
    }

    public function storeManager(StoreManagerRequest $request, StallLayoutService $layout): RedirectResponse
    {
        $manager = User::create([
            ...$request->safe()->only(['name', 'email', 'password']),
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $emailed = SafeMail::attempt(fn () => event(new Registered($manager)), ['user' => $manager->id]); // verification link
        $layout->sync($manager);         // first manager gets the generated stalls

        $this->logActivity('created_manager', $request->user()->name.' created manager account for '.$manager->name, $manager);

        return redirect()->route('manager.users')->with('success', $emailed
            ? 'Manager account created successfully. A verification email was sent.'
            : 'Manager account created, but the verification email could not be sent. They can use "Resend verification email" after signing in.');
    }
}

