<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Services\StaffTwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SecurityController extends Controller
{
    public function index(Request $request, StaffTwoFactor $mfa)
    {
        $user = $request->user()->fresh();
        $codes = $request->session()->pull('recovery_codes', []);

        return view('staff.security', ['staffUser' => $user, 'qrCode' => $mfa->qrCode($user), 'recoveryCodes' => $codes]);
    }

    public function begin(Request $request, StaffTwoFactor $mfa)
    {
        $data = $request->validate(['code' => ['nullable', 'string', 'max:40']]);
        $mfa->begin($request->user(), $data['code'] ?? null);

        return redirect()->route('staff.security.index')->with('status', 'Scan the new QR code, then confirm with your authenticator. Existing protection stays active until confirmation.');
    }

    public function confirm(Request $request, StaffTwoFactor $mfa)
    {
        $data = $request->validate(['code' => ['required', 'regex:/^\d{6}$/D']]);
        $codes = $mfa->confirm($request->user(), $data['code']);
        $mfa->markCurrentSession($request);

        return redirect()->route('staff.security.index')->with('recovery_codes', $codes)->with('status', 'Two-factor authentication enabled. Save your recovery codes now; they are shown once. Other sessions have been revoked.');
    }

    public function recovery(Request $request, StaffTwoFactor $mfa)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);
        $codes = $mfa->regenerateRecovery($request->user(), $data['code']);
        $mfa->markCurrentSession($request);

        return redirect()->route('staff.security.index')->with('recovery_codes', $codes)->with('status', 'New recovery codes created. Previous codes and other sessions are no longer valid.');
    }

    public function disable(Request $request, StaffTwoFactor $mfa)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40'], 'confirm_disable' => ['accepted']]);
        $mfa->disable($request->user(), $data['code']);
        $mfa->markCurrentSession($request);

        return redirect()->route('staff.security.index')->with('status', 'Two-factor authentication disabled. Other sessions have been revoked.');
    }

    public function cancelSetup(Request $request)
    {
        $user = $request->user();
        $user->two_factor_pending_secret = null;
        $user->two_factor_pending_expires_at = null;
        $user->save();

        return redirect()->route('staff.security.index')->with('status', 'Pending authenticator setup cancelled. Existing protection is unchanged.');
    }

    public function challengeForm(Request $request, StaffTwoFactor $mfa)
    {
        if (! $mfa->pendingUser($request)) {
            $request->session()->forget('staff_pending_mfa');

            return redirect()->route('staff.login')->with('status', 'Your verification window expired. Sign in again.');
        }

        return view('staff.auth.challenge');
    }

    public function challenge(Request $request, StaffTwoFactor $mfa)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);
        $user = $mfa->pendingUser($request);
        if (! $user) {
            $request->session()->forget('staff_pending_mfa');

            return redirect()->route('staff.login')->with('status', 'Your verification window expired. Sign in again.');
        }
        $user = $mfa->verify($user, $data['code']);
        // Recheck after the locked verification so suspension/reset cannot be bypassed.
        $pending = $request->session()->get('staff_pending_mfa');
        if ($user->session_version !== $pending['version']) {
            throw ValidationException::withMessages(['code' => 'Your account changed. Sign in again.']);
        }
        Auth::login($user, false);
        $request->session()->forget('staff_pending_mfa');
        $mfa->markCurrentSession($request);
        $request->session()->put('auth.password_confirmed_at', time());
        AuditEvent::record($user, 'staff.login', $user);

        return redirect()->intended(route('staff.dashboard'));
    }
}
