<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\User;
use App\Services\StaffTwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private Google2FA $clock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clock = new class extends Google2FA
        {
            public int $step = 1000000;

            public function getTimestamp()
            {
                return $this->step;
            }
        };
        $this->app->instance(Google2FA::class, $this->clock);
    }

    private function enroll(User $user): array
    {
        $service = app(StaffTwoFactor::class);
        $service->begin($user, null);
        $codes = $service->confirm($user, $this->clock->getCurrentOtp($user->fresh()->two_factor_pending_secret));
        $user->refresh();

        return $codes;
    }

    public function test_enrollment_requires_password_and_confirmation_and_encrypts_secrets(): void
    {
        $user = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->actingAs($user)->post('/staff/security/two-factor')->assertRedirect('/staff/confirm-password');
        $this->withSession(['auth.password_confirmed_at' => time()])->post('/staff/security/two-factor')->assertRedirect('/staff/security');
        $user->refresh();
        $secret = $user->two_factor_pending_secret;
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertNotSame($secret, DB::table('users')->where('id', $user->id)->value('two_factor_pending_secret'));
        $this->get('/staff/security')->assertOk()->assertSee('<svg', false)->assertDontSee('api.qrserver.com');
        $this->post('/staff/security/two-factor/confirm', ['code' => $this->clock->getCurrentOtp($secret)])->assertRedirect('/staff/security')->assertSessionHas('recovery_codes');
        $user->refresh();
        $this->assertSame(1000000, $user->two_factor_last_counter);
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertNull($user->two_factor_pending_secret);
        $this->assertCount(10, $user->two_factor_recovery_codes);
        $this->assertArrayNotHasKey('two_factor_secret', $user->toArray());
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $user->toArray());
        $this->get('/staff/security')->assertOk()->assertSee('Save these recovery codes now');
        $this->get('/staff/security')->assertOk()->assertDontSee('Save these recovery codes now');
        $this->assertDatabaseHas('audit_events', ['action' => 'staff.mfa_enabled']);
    }

    public function test_password_only_cannot_enter_workspace_and_otp_cannot_be_replayed(): void
    {
        $user = User::factory()->create(['password' => 'TestAccountPassword123']);
        $this->enroll($user);
        $this->post('/staff/login', ['email' => $user->email, 'password' => 'TestAccountPassword123'])->assertRedirect('/staff/two-factor-challenge');
        $this->assertGuest();
        $this->get('/staff')->assertRedirect('/staff/login');
        $this->post('/staff/two-factor-challenge', ['code' => $this->clock->getCurrentOtp($user->two_factor_secret)])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->clock->step++;
        $otp = $this->clock->getCurrentOtp($user->two_factor_secret);
        $this->post('/staff/two-factor-challenge', ['code' => $otp])->assertRedirect('/staff');
        $this->assertAuthenticatedAs($user);
        $this->post('/staff/logout');
        $this->post('/staff/login', ['email' => $user->email, 'password' => 'TestAccountPassword123']);
        $this->post('/staff/two-factor-challenge', ['code' => $otp])->assertSessionHasErrors('code')->assertSessionMissing('_old_input.code');
        $this->assertGuest();
    }

    public function test_recovery_code_is_single_use_and_account_changes_invalidate_pending_login(): void
    {
        $user = User::factory()->create(['password' => 'TestAccountPassword123']);
        $codes = $this->enroll($user);
        $this->post('/staff/login', ['email' => $user->email, 'password' => 'TestAccountPassword123']);
        $this->post('/staff/two-factor-challenge', ['code' => $codes[0]])->assertRedirect('/staff');
        $this->assertCount(9, $user->fresh()->two_factor_recovery_codes);
        $this->post('/staff/logout');
        $this->post('/staff/login', ['email' => $user->email, 'password' => 'TestAccountPassword123']);
        $this->post('/staff/two-factor-challenge', ['code' => $codes[0]])->assertSessionHasErrors('code');
        $user->increment('session_version');
        $this->post('/staff/two-factor-challenge', ['code' => $codes[1]])->assertRedirect('/staff/login');
        $this->assertGuest();
        $this->assertCount(9, $user->fresh()->two_factor_recovery_codes);
    }

    public function test_expired_setup_or_login_cannot_complete_and_challenge_is_rate_limited(): void
    {
        $user = User::factory()->create(['password' => 'TestAccountPassword123']);
        $service = app(StaffTwoFactor::class);
        $service->begin($user, null);
        $user->refresh();
        $secret = $user->two_factor_pending_secret;
        $user->two_factor_pending_expires_at = now()->subMinute();
        $user->save();
        try {
            $service->confirm($user, $this->clock->getCurrentOtp($secret));
            $this->fail('Expired setup must not activate protection.');
        } catch (ValidationException) {
            $this->assertNull($user->fresh()->two_factor_confirmed_at);
        }
        $this->enroll($user);
        $this->post('/staff/login', ['email' => $user->email, 'password' => 'TestAccountPassword123']);
        $this->withSession(['staff_pending_mfa' => ['id' => $user->id, 'version' => $user->session_version, 'expires' => time() - 1]])->post('/staff/two-factor-challenge', ['code' => 'invalid'])->assertRedirect('/staff/login');
        $this->post('/staff/login', ['email' => $user->email, 'password' => 'TestAccountPassword123']);
        for ($i = 0; $i < 4; $i++) {
            $this->post('/staff/two-factor-challenge', ['code' => 'invalid'])->assertSessionHasErrors('code');
        }
        $this->post('/staff/two-factor-challenge', ['code' => 'invalid'])->assertStatus(429);
    }

    public function test_replacement_preserves_current_device_until_confirmed_and_rotates_recovery_codes(): void
    {
        $user = User::factory()->create();
        $oldCodes = $this->enroll($user);
        $oldSecret = $user->two_factor_secret;
        $service = app(StaffTwoFactor::class);
        $service->begin($user, $oldCodes[0]);
        $user->refresh();
        $this->assertSame($oldSecret, $user->two_factor_secret);
        $newSecret = $user->two_factor_pending_secret;
        $this->assertNotSame($newSecret, $oldSecret);
        $newCodes = $service->confirm($user, $this->clock->getCurrentOtp($newSecret));
        $user->refresh();
        $this->assertSame($newSecret, $user->two_factor_secret);
        $this->assertNotSame($oldCodes, $newCodes);
        try {
            $service->verify($user, $oldCodes[1]);
            $this->fail('Old recovery codes must not work after device replacement.');
        } catch (ValidationException) {
            $this->assertCount(10, $user->fresh()->two_factor_recovery_codes);
        }
    }

    public function test_mandatory_admin_enrollment_cannot_be_bypassed_or_disabled(): void
    {
        config(['security.require_admin_two_factor' => true]);
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
        $this->get('/staff')->assertRedirect('/staff/security');
        $this->get('/staff/growth/integrations')->assertRedirect('/staff/security');
        $this->get('/staff/security')->assertOk();
        $codes = $this->enroll($admin);
        $this->actingAs($admin->fresh())->withSession(['staff_session_version' => $admin->session_version, 'staff_mfa_verified' => $admin->session_version]);
        $this->delete('/staff/security/two-factor', ['code' => $codes[0], 'confirm_disable' => 1])->assertSessionHasErrors('code');
        $this->assertNotNull($admin->fresh()->two_factor_confirmed_at);
    }

    public function test_existing_authenticated_session_still_requires_mfa_when_enabled(): void
    {
        $user = User::factory()->create();
        $this->enroll($user);
        $this->actingAs($user)->withSession(['staff_session_version' => $user->session_version])->get('/staff/articles')->assertRedirect('/staff/two-factor-challenge');
        $this->assertGuest();
    }

    public function test_operator_recovery_requires_explicit_reason_and_preserves_password_and_role(): void
    {
        $user = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->enroll($user);
        $oldPassword = $user->password;
        $oldVersion = $user->session_version;
        $this->artisan('staff:reset-two-factor', ['email' => $user->email])->assertExitCode(1);
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->artisan('staff:reset-two-factor', ['email' => $user->email, '--force' => true, '--reason' => 'Disposable test: identity verified offline by the trusted operator.'])->assertSuccessful();
        $user->refresh();
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertNull($user->two_factor_secret);
        $this->assertSame($oldPassword, $user->password);
        $this->assertSame(StaffRole::SuperAdmin, $user->role);
        $this->assertGreaterThan($oldVersion, $user->session_version);
        $this->assertDatabaseHas('audit_events', ['action' => 'staff.mfa_operator_reset']);
    }
}
