<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\AuditEvent;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_authors_cannot_manage_team_or_forge_an_administrator_invitation(): void
    {
        $author = User::factory()->create();
        $this->actingAs($author)->get('/staff/users')->assertForbidden();
        $this->post('/staff/invitations', ['email' => 'forged@example.test', 'name' => 'Forged', 'role' => 'super_admin'])->assertForbidden();
        $this->patch('/staff/users/'.$author->id, ['role' => 'super_admin', 'is_active' => 1, 'version' => 1])->assertForbidden();
        $this->assertDatabaseCount('staff_invitations', 0);
        $this->assertSame(StaffRole::Author, $author->fresh()->role);
    }

    public function test_admin_invites_only_authors_and_can_revoke_an_invitation(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
        $response = $this->from('/staff/users')->post('/staff/invitations', ['email' => 'author@example.test', 'name' => 'Invited', 'role' => 'super_admin'])->assertRedirect('/staff/users')->assertSessionHas('invite_url');
        $this->assertSame(StaffRole::Author, StaffInvitation::first()->role);
        $this->get('/staff/users')->assertOk()->assertSee('One-time account setup link');
        $invite = StaffInvitation::first();
        $this->delete('/staff/invitations/'.$invite->id)->assertRedirect();
        $this->assertTrue($invite->fresh()->expires_at->isPast());
        $this->assertTrue(AuditEvent::where('action', 'invitation.revoked')->exists());
    }

    public function test_last_active_super_admin_cannot_be_suspended_or_demoted(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
        $this->patch('/staff/users/'.$admin->id, ['role' => 'author', 'is_active' => 1, 'version' => 1])->assertSessionHasErrors('users');
        $this->patch('/staff/users/'.$admin->id, ['role' => 'super_admin', 'is_active' => 0, 'version' => 1])->assertSessionHasErrors('users');
        $this->assertTrue($admin->fresh()->isSuperAdmin());
    }

    public function test_admin_can_suspend_an_author_and_old_updates_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $author = User::factory()->create();
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
        $this->patch('/staff/users/'.$author->id, ['role' => 'author', 'is_active' => 0, 'version' => 1])->assertRedirect();
        $this->assertFalse($author->fresh()->is_active);
        $this->assertSame(2, $author->fresh()->session_version);
        $this->patch('/staff/users/'.$author->id, ['role' => 'super_admin', 'is_active' => 1, 'version' => 1])->assertSessionHasErrors('users');
        $this->assertSame(StaffRole::Author, $author->fresh()->role);
    }

    public function test_team_changes_require_recent_password_confirmation(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin, 'password' => 'TestAccountPassword123']);
        $this->actingAs($admin)->get('/staff/users')->assertRedirect('/staff/confirm-password');
        $this->post('/staff/confirm-password', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post('/staff/confirm-password', ['password' => 'TestAccountPassword123'])->assertRedirect('/staff/users');
        $this->get('/staff/users')->assertOk();
    }

    public function test_failed_invitation_email_keeps_the_setup_link_available_to_the_admin(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        config(['mail.default' => 'smtp']);
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->post('/staff/invitations', ['email' => 'invite@example.test', 'name' => 'Invited'])
            ->assertRedirect()->assertSessionHas('invite_url')->assertSessionHas('delivery_failed', true);
        $this->assertDatabaseCount('staff_invitations', 1);
        $this->assertTrue(AuditEvent::where('action', 'invitation.delivery_failed')->exists());
    }
}
