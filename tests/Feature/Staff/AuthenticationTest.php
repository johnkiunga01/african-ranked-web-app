<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\StaffInvitation;
use App\Models\User;
use App\Services\StaffInvitations;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_is_invite_only_and_guests_cannot_open_workspace(): void
    {
        $this->get('/staff')->assertRedirect('/staff/login');
        $this->get('/staff/articles/create')->assertRedirect('/staff/login');
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['role' => 'super_admin'])->assertNotFound();
        $this->get('/staff/login')->assertOk()->assertSee('invitation-only');
    }

    public function test_login_logout_and_private_workspace_headers(): void
    {
        $user = User::factory()->create(['password' => 'TestAccountPassword123']);
        $this->post('/staff/login', ['email' => strtoupper($user->email), 'password' => 'TestAccountPassword123'])->assertRedirect('/staff');
        $this->assertAuthenticatedAs($user);
        $this->get('/staff')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')->assertSee('Your publication, at a glance.');
        $this->post('/staff/logout')->assertRedirect('/staff/login');
        $this->assertGuest();
    }

    public function test_inactive_unverified_and_invalid_logins_are_rejected_and_throttled(): void
    {
        $inactive = User::factory()->create(['is_active' => false, 'password' => 'TestAccountPassword123']);
        $unverified = User::factory()->unverified()->create(['password' => 'TestAccountPassword123']);
        foreach ([$inactive, $unverified] as $user) {
            $this->post('/staff/login', ['email' => $user->email, 'password' => 'TestAccountPassword123'])->assertSessionHasErrors('email');
            $this->assertGuest();
        }
        for ($i = 0; $i < 5; $i++) {
            $this->post('/staff/login', ['email' => 'missing@example.test', 'password' => 'incorrect'])->assertSessionHasErrors('email');
        }
        $this->post('/staff/login', ['email' => 'missing@example.test', 'password' => 'incorrect'])->assertStatus(429);
    }

    public function test_invitation_is_hashed_single_use_and_role_cannot_be_forged(): void
    {
        [$invite, $url] = app(StaffInvitations::class)->issue('new-author@example.test', 'Invited Author', StaffRole::Author);
        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->assertNotSame($token, $invite->token_hash);
        $this->get($url)->assertOk()->assertSee('Set up your account');
        $this->post($url, ['name' => 'Actual Author', 'password' => 'NewAccountPassword123', 'password_confirmation' => 'NewAccountPassword123', 'role' => 'super_admin', 'email' => 'forged@example.test'])->assertRedirect('/staff');
        $user = User::where('email', 'new-author@example.test')->firstOrFail();
        $this->assertSame(StaffRole::Author, $user->role);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(Hash::check('NewAccountPassword123', $user->password));
        $this->assertNotNull($invite->fresh()->accepted_at);
        $this->post('/staff/logout');
        $this->get($url)->assertNotFound();
        $this->post($url, ['name' => 'Again', 'password' => 'NewAccountPassword123', 'password_confirmation' => 'NewAccountPassword123'])->assertStatus(410);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_expired_revoked_and_reissued_invites_do_not_work(): void
    {
        [$first, $firstUrl] = app(StaffInvitations::class)->issue('new@example.test', 'New Author', StaffRole::Author);
        [, $secondUrl] = app(StaffInvitations::class)->issue('new@example.test', 'New Author', StaffRole::Author);
        $this->get($firstUrl)->assertNotFound();
        $this->get($secondUrl)->assertOk();
        StaffInvitation::where('email', 'new@example.test')->update(['expires_at' => now()->subMinute()]);
        $this->get($secondUrl)->assertNotFound();
        $this->get('/staff/invitations/not-a-token')->assertNotFound();
    }

    public function test_weak_invitation_password_does_not_create_account(): void
    {
        [, $url] = app(StaffInvitations::class)->issue('new@example.test', 'New Author', StaffRole::Author);
        $this->post($url, ['name' => 'New Author', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_reset_uses_configured_link_and_revokes_previous_sessions(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'OldAccountPassword123']);
        $this->post('/staff/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });
        $this->get('/staff/reset-password/'.$token.'?email='.urlencode($user->email))->assertOk();
        $this->post('/staff/reset-password', ['token' => $token, 'email' => $user->email, 'password' => 'ChangedAccountPassword123', 'password_confirmation' => 'ChangedAccountPassword123'])->assertRedirect('/staff/login');
        $this->assertTrue(Hash::check('ChangedAccountPassword123', $user->fresh()->password));
        $this->assertSame(2, $user->fresh()->session_version);
        $this->actingAs($user->fresh())->withSession(['staff_session_version' => 1])->get('/staff')->assertRedirect('/staff/login');
        $this->assertGuest();
    }

    public function test_suspension_and_version_changes_invalidate_existing_access(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['staff_session_version' => 1])->get('/staff')->assertOk();
        $user->forceFill(['is_active' => false])->save();
        $this->get('/staff')->assertRedirect('/staff/login');
        $this->assertGuest();
    }

    public function test_unknown_password_reset_returns_generic_status_without_mail(): void
    {
        Notification::fake();
        $this->post('/staff/forgot-password', ['email' => 'not-a-member@example.test'])->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_role_and_activation_are_not_mass_assignable(): void
    {
        $user = new User(['name' => 'Safe', 'email' => 'safe@example.test', 'password' => 'TestPassword12345', 'role' => 'super_admin', 'is_active' => false]);
        $this->assertSame(StaffRole::Author, $user->role);
        $this->assertTrue($user->is_active);
    }
}
