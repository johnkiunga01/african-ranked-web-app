<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\StaffInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_setup_is_private_single_use_and_never_seeded_as_a_default_password(): void
    {
        $path = storage_path('app/private/test-'.Str::uuid().'.url');
        try {
            $this->artisan('staff:invite-admin', ['email' => 'owner@example.test', '--link-file' => $path])->assertSuccessful();
            $invitation = StaffInvitation::firstOrFail();
            $this->assertSame(StaffRole::SuperAdmin, $invitation->role);
            $this->assertDatabaseCount('users', 0);
            $this->assertFileExists($path);
            $this->assertSame(0600, fileperms($path) & 0777);
            $url = trim(file_get_contents($path));
            $this->get($url)->assertOk()->assertSee('Super admin');
            $this->post($url, ['name' => 'Actual Owner', 'password' => 'OwnerChosenPassword123', 'password_confirmation' => 'OwnerChosenPassword123'])->assertRedirect('/staff');
            $this->get('/staff/users')->assertOk();
            $this->get('/storage/'.basename($path))->assertForbidden();
        } finally {
            @unlink($path);
        }
    }

    public function test_provisioning_does_not_overwrite_an_existing_link_file_or_write_into_public(): void
    {
        $path = storage_path('app/private/test-'.Str::uuid().'.url');
        @mkdir(dirname($path), 0700, true);
        file_put_contents($path, 'existing-owner-file');
        try {
            $this->artisan('staff:invite-admin', ['email' => 'owner@example.test', '--link-file' => $path])->assertFailed();
            $this->assertSame('existing-owner-file', file_get_contents($path));
            $this->artisan('staff:invite-admin', ['email' => 'owner@example.test', '--link-file' => public_path('setup.url')])->assertFailed();
            $this->assertDatabaseCount('staff_invitations', 0);
        } finally {
            @unlink($path);
        }
    }
}
