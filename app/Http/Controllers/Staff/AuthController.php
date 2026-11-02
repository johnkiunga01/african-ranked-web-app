<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\User;
use App\Services\StaffInvitations;
use App\Services\StaffTwoFactor;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('staff.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', mb_strtolower(trim($data['email'])))->first();
        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            // Perform a hash check even for an unknown address to reduce timing differences.
            if (! $user) {
                Hash::check($data['password'], '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
            }
            throw ValidationException::withMessages(['email' => 'These credentials could not be used to sign in.']);
        }
        if ($user->two_factor_confirmed_at) {
            app(StaffTwoFactor::class)->startChallenge($request, $user);

            return redirect()->route('staff.two-factor.challenge');
        }
        Auth::login($user, false);
        $request->session()->regenerate();
        $request->session()->put('staff_session_version', $user->session_version);
        $request->session()->put('auth.password_confirmed_at', time());
        AuditEvent::record($user, 'staff.login', $user);

        return redirect()->intended(route('staff.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }

    public function invitation(string $token, StaffInvitations $invitations)
    {
        return view('staff.auth.invitation', ['invitation' => $invitations->resolve($token), 'token' => $token]);
    }

    public function accept(Request $request, string $token, StaffInvitations $invitations)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()]]);
        $user = $invitations->accept($token, $data['name'], $data['password']);
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('staff_session_version', $user->session_version);
        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->route('staff.dashboard');
    }

    public function forgotForm()
    {
        return view('staff.auth.forgot');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $email = mb_strtolower(trim($request->string('email')->toString()));
        $user = User::where('email', $email)->where('is_active', true)->first();
        if ($user && $user->hasVerifiedEmail()) {
            Password::sendResetLink(['email' => $email]);
        }

        return back()->with('status', 'If an eligible account exists, password-reset instructions will be sent using the configured mail service.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('staff.auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()]]);
        $status = Password::reset($data, function (User $user, string $password) {
            abort_unless($user->is_active && $user->hasVerifiedEmail(), 403);
            $user->password = $password;
            $user->remember_token = Str::random(60);
            $user->session_version++;
            $user->save();
            AuditEvent::record($user, 'staff.password_reset', $user);
            event(new PasswordReset($user));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect()->route('staff.login')->with('status', 'Password updated. Please sign in again.');
    }

    public function confirmForm()
    {
        return view('staff.auth.confirm');
    }

    public function confirm(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'The password is incorrect.']);
        }
        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('staff.dashboard'));
    }
}
