<?php

namespace App\Services;

use App\Enums\StaffRole;
use App\Models\AuditEvent;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StaffInvitations
{
    public function issue(string $email, string $name, StaffRole $role, ?User $actor = null): array
    {
        $email = mb_strtolower(trim($email));
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'An account already uses this email address.']);
        }

        return DB::transaction(function () use ($email, $name, $role, $actor) {
            // Reissuing revokes earlier links for this address.
            StaffInvitation::where('email', $email)->whereNull('accepted_at')->update(['expires_at' => now()]);
            $token = Str::random(64);
            $invitation = StaffInvitation::create(['email' => $email, 'name' => $name, 'role' => $role, 'token_hash' => hash('sha256', $token), 'invited_by' => $actor?->id, 'expires_at' => now()->addHours(config('editorial.invitation_hours'))]);
            AuditEvent::record($actor, 'invitation.created', $invitation, ['role' => $role->value]);

            return [$invitation, route('staff.invitation', ['token' => $token])];
        });
    }

    public function resolve(string $token): StaffInvitation
    {
        abort_unless(strlen($token) === 64, 404);

        return StaffInvitation::where('token_hash', hash('sha256', $token))->whereNull('accepted_at')->where('expires_at', '>', now())->firstOrFail();
    }

    public function accept(string $token, string $name, string $password): User
    {
        return DB::transaction(function () use ($token, $name, $password) {
            $invitation = StaffInvitation::where('token_hash', hash('sha256', $token))->lockForUpdate()->firstOrFail();
            abort_if($invitation->accepted_at || $invitation->expires_at->isPast(), 410);
            if (User::where('email', $invitation->email)->exists()) {
                throw ValidationException::withMessages(['email' => 'This invitation can no longer be used.']);
            }
            $user = new User(['name' => $name, 'email' => $invitation->email, 'password' => $password]);
            $user->role = $invitation->role;
            $user->is_active = true;
            $user->email_verified_at = now();
            $user->save();
            $invitation->update(['accepted_at' => now()]);
            AuditEvent::record($user, 'invitation.accepted', $invitation);

            return $user;
        });
    }
}
