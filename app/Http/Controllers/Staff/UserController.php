<?php

namespace App\Http\Controllers\Staff;

use App\Enums\StaffRole;
use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\StaffInvitation;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use App\Services\StaffInvitations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-staff');

        return view('staff.users.index', [
            'users' => User::whereNull('removed_at')->orderBy('name')->get(),
            'removedUsers' => User::whereNotNull('removed_at')->orderBy('name')->get(),
            'invitations' => StaffInvitation::whereNull('accepted_at')->where('expires_at', '>', now())->latest()->get(),
        ]);
    }

    public function invite(Request $request, StaffInvitations $invitations)
    {
        Gate::authorize('manage-staff');
        $data = $request->validate(['email' => ['required', 'email', 'max:254'], 'name' => ['required', 'string', 'max:100']]);
        [$invitation, $url] = $invitations->issue($data['email'], $data['name'], StaffRole::Author, $request->user());
        // Copyable setup links keep review usable when outbound email is not configured.
        $deliveryFailed = false;
        if (config('mail.default') !== 'log') {
            try {
                Notification::route('mail', $invitation->email)->notify(new StaffInvitationNotification($url));
            } catch (\Throwable $exception) {
                $deliveryFailed = true;
                AuditEvent::record($request->user(), 'invitation.delivery_failed', $invitation);
            }
        }

        return back()->with('status', 'Author invitation created. The link expires in 24 hours and works once.')->with('invite_url', $url)->with('delivery_failed', $deliveryFailed);
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('manage-staff');
        $data = $request->validate(['role' => ['required', Rule::in(['author', 'super_admin'])], 'is_active' => ['required', 'boolean'], 'version' => ['required', 'integer']]);
        DB::transaction(function () use ($request, $user, $data) {
            // Serialize role/suspension changes against the active administrator set.
            $admins = User::where('role', StaffRole::SuperAdmin->value)->where('is_active', true)->whereNotNull('email_verified_at')->lockForUpdate()->get();
            $target = User::lockForUpdate()->findOrFail($user->id);
            if ($target->removed_at || $target->session_version !== (int) $data['version']) {
                throw ValidationException::withMessages(['users' => 'This account changed. Reload and try again.']);
            }
            if ($target->role === StaffRole::SuperAdmin && $target->is_active && ($data['role'] !== StaffRole::SuperAdmin->value || ! $data['is_active']) && $admins->count() <= 1) {
                throw ValidationException::withMessages(['users' => 'Keep at least one active super admin.']);
            }
            if ($data['role'] === StaffRole::SuperAdmin->value && ! $target->hasVerifiedEmail()) {
                throw ValidationException::withMessages(['users' => 'An administrator account must have a verified email address.']);
            }
            $target->role = StaffRole::from($data['role']);
            $target->is_active = (bool) $data['is_active'];
            $target->session_version++;
            $target->setRememberToken(null);
            $target->save();
            AuditEvent::record($request->user(), 'staff.access_changed', $target, ['role' => $target->role->value, 'is_active' => $target->is_active]);
        });

        return back()->with('status', 'Access updated. Previous sessions have been revoked.');
    }

    public function destroy(Request $request, User $user)
    {
        Gate::authorize('manage-staff');
        $data = $request->validate(['version' => ['required', 'integer'], 'confirm_removal' => ['accepted']]);
        DB::transaction(function () use ($request, $user, $data) {
            $admins = User::where('role', StaffRole::SuperAdmin->value)->where('is_active', true)->whereNotNull('email_verified_at')->lockForUpdate()->get();
            $target = User::lockForUpdate()->findOrFail($user->id);
            if ($target->session_version !== (int) $data['version'] || $target->removed_at) {
                throw ValidationException::withMessages(['users' => 'This account changed. Reload before removing it.']);
            }
            if ($target->id === $request->user()->id) {
                throw ValidationException::withMessages(['users' => 'You cannot remove your own account. Ask another super admin.']);
            }
            if ($target->isSuperAdmin() && $admins->count() <= 1) {
                throw ValidationException::withMessages(['users' => 'Keep at least one active, verified super admin.']);
            }
            $target->is_active = false;
            $target->removed_at = now();
            $target->session_version++;
            $target->setRememberToken(null);
            $target->two_factor_pending_secret = null;
            $target->two_factor_pending_expires_at = null;
            $target->save();
            DB::table('sessions')->where('user_id', $target->id)->delete();
            DB::table('password_reset_tokens')->where('email', $target->email)->delete();
            StaffInvitation::where('email', $target->email)->whereNull('accepted_at')->update(['expires_at' => now()]);
            AuditEvent::record($request->user(), 'staff.removed', $target);
        });

        return back()->with('status', 'Staff access removed and sessions revoked. Article attribution, history and the reserved email are retained.');
    }

    public function restore(Request $request, User $user)
    {
        Gate::authorize('manage-staff');
        $data = $request->validate(['version' => ['required', 'integer']]);
        DB::transaction(function () use ($request, $user, $data) {
            $target = User::lockForUpdate()->findOrFail($user->id);
            if (! $target->removed_at || $target->session_version !== (int) $data['version']) {
                throw ValidationException::withMessages(['users' => 'This account changed. Reload before restoring it.']);
            }
            $target->removed_at = null;
            $target->is_active = false;
            $target->session_version++;
            $target->save();
            AuditEvent::record($request->user(), 'staff.restored', $target);
        });

        return back()->with('status', 'Staff member restored as suspended. Review their role before enabling access.');
    }

    public function revoke(StaffInvitation $invitation)
    {
        Gate::authorize('manage-staff');
        $invitation->update(['expires_at' => now()]);
        AuditEvent::record(auth()->user(), 'invitation.revoked', $invitation);

        return back()->with('status', 'Invitation revoked.');
    }
}
