<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class StaffTwoFactor
{
    public function begin(User $actor, #[\SensitiveParameter] ?string $code): void
    {
        DB::transaction(function () use ($actor, $code) {
            $user = User::lockForUpdate()->findOrFail($actor->id);
            if ($user->two_factor_confirmed_at) {
                $this->verifyLocked($user, $code ?? '');
            }
            $user->two_factor_pending_secret = app(Google2FA::class)->generateSecretKey(32);
            $user->two_factor_pending_expires_at = now()->addMinutes(config('security.setup_minutes'));
            $user->save();
            AuditEvent::record($user, 'staff.mfa_setup_started', $user);
        });
    }

    public function confirm(User $actor, #[\SensitiveParameter] string $code): array
    {
        return DB::transaction(function () use ($actor, $code) {
            $user = User::lockForUpdate()->findOrFail($actor->id);
            if (! $user->two_factor_pending_secret || ! $user->two_factor_pending_expires_at?->isFuture()) {
                throw ValidationException::withMessages(['code' => 'Authenticator setup expired. Start setup again.']);
            }
            $counter = app(Google2FA::class)->verifyKeyNewer($user->two_factor_pending_secret, $code, 0, 1);
            if ($counter === false) {
                $this->invalid();
            }
            $codes = $this->newRecoveryCodes();
            $user->two_factor_secret = $user->two_factor_pending_secret;
            $user->two_factor_pending_secret = null;
            $user->two_factor_pending_expires_at = null;
            $user->two_factor_confirmed_at = now();
            $user->two_factor_last_counter = $counter;
            $user->two_factor_recovery_codes = array_map(fn ($c) => $this->recoveryHash($c), $codes);
            $user->session_version++;
            $user->save();
            AuditEvent::record($user, 'staff.mfa_enabled', $user);

            return $codes;
        });
    }

    public function verify(User $actor, #[\SensitiveParameter] string $code): User
    {
        return DB::transaction(function () use ($actor, $code) {
            $user = User::lockForUpdate()->findOrFail($actor->id);
            $this->verifyLocked($user, $code);

            return $user;
        });
    }

    public function regenerateRecovery(User $actor, #[\SensitiveParameter] string $code): array
    {
        return DB::transaction(function () use ($actor, $code) {
            $user = User::lockForUpdate()->findOrFail($actor->id);
            $this->verifyLocked($user, $code);
            $codes = $this->newRecoveryCodes();
            $user->two_factor_recovery_codes = array_map(fn ($c) => $this->recoveryHash($c), $codes);
            $user->session_version++;
            $user->save();
            AuditEvent::record($user, 'staff.mfa_recovery_regenerated', $user);

            return $codes;
        });
    }

    public function disable(User $actor, #[\SensitiveParameter] string $code): void
    {
        DB::transaction(function () use ($actor, $code) {
            $user = User::lockForUpdate()->findOrFail($actor->id);
            if ($user->isSuperAdmin() && config('security.require_admin_two_factor')) {
                throw ValidationException::withMessages(['code' => 'Two-factor authentication is required for super admins. Use the device-replacement flow instead.']);
            }
            $this->verifyLocked($user, $code);
            $user->two_factor_secret = null;
            $user->two_factor_pending_secret = null;
            $user->two_factor_pending_expires_at = null;
            $user->two_factor_confirmed_at = null;
            $user->two_factor_recovery_codes = null;
            $user->two_factor_last_counter = null;
            $user->session_version++;
            $user->save();
            AuditEvent::record($user, 'staff.mfa_disabled', $user);
        });
    }

    public function startChallenge(Request $request, User $user): void
    {
        Auth::logout();
        $request->session()->regenerate(true);
        $request->session()->forget(['staff_session_version', 'staff_mfa_verified', 'auth.password_confirmed_at', 'recovery_codes']);
        $request->session()->put('staff_pending_mfa', ['id' => $user->id, 'version' => $user->session_version, 'expires' => time() + config('security.challenge_seconds')]);
    }

    public function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get('staff_pending_mfa');
        if (! is_array($pending) || ($pending['expires'] ?? 0) <= time()) {
            return null;
        }
        $user = User::find($pending['id'] ?? 0);

        return $user && $user->is_active && $user->hasVerifiedEmail() && $user->two_factor_confirmed_at && $user->session_version === ($pending['version'] ?? null) ? $user : null;
    }

    public function markCurrentSession(Request $request): void
    {
        $user = $request->user()->fresh();
        Auth::setUser($user);
        $request->session()->regenerate(true);
        $request->session()->put('staff_session_version', $user->session_version);
        $request->session()->put('staff_mfa_verified', $user->session_version);
    }

    public function qrCode(User $user): ?string
    {
        if (! $user->two_factor_pending_secret || ! $user->two_factor_pending_expires_at?->isFuture()) {
            return null;
        }
        $uri = 'otpauth://totp/'.rawurlencode(config('app.name').':'.$user->email).'?'.http_build_query(['secret' => $user->two_factor_pending_secret, 'issuer' => config('app.name'), 'algorithm' => 'SHA1', 'digits' => 6, 'period' => 30], '', '&', PHP_QUERY_RFC3986);

        return (new Writer(new ImageRenderer(new RendererStyle(224), new SvgImageBackEnd)))->writeString($uri);
    }

    private function verifyLocked(User $user, #[\SensitiveParameter] string $code): void
    {
        if (! $user->is_active || ! $user->hasVerifiedEmail() || ! $user->two_factor_confirmed_at || ! $user->two_factor_secret) {
            $this->invalid();
        }
        if (preg_match('/^\d{6}$/D', $code)) {
            $counter = app(Google2FA::class)->verifyKeyNewer($user->two_factor_secret, $code, $user->two_factor_last_counter ?? 0, 1);
            if ($counter === false) {
                $this->invalid();
            }
            $user->two_factor_last_counter = $counter;
            $user->save();

            return;
        }
        $hash = $this->recoveryHash($code);
        $codes = $user->two_factor_recovery_codes ?? [];
        foreach ($codes as $index => $saved) {
            if (hash_equals($saved, $hash)) {
                unset($codes[$index]);
                $user->two_factor_recovery_codes = array_values($codes);
                $user->save();
                AuditEvent::record($user, 'staff.mfa_recovery_used', $user);

                return;
            }
        }
        $this->invalid();
    }

    private function newRecoveryCodes(): array
    {
        return array_map(fn () => implode('-', str_split(strtoupper(bin2hex(random_bytes(10))), 5)), range(1, 10));
    }

    private function recoveryHash(#[\SensitiveParameter] string $code): string
    {
        return hash('sha256', strtolower(str_replace('-', '', trim($code))));
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['code' => 'That code is invalid, expired or already used. Try a new authenticator code or an unused recovery code.']);
    }
}
