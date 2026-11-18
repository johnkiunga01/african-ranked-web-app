<?php

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaInstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_and_offline_resources_are_present_and_public_layout_links_to_them(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#fbfaf7', $manifest['background_color']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame(4, count($manifest['icons']));
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));
        $response = $this->get('/')->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('/manifest.webmanifest', false)
            ->assertSee('data-pwa-open', false)
            ->assertSee('data-pwa-launch', false)
            ->assertSee('Explore Africa through rankings, culture &amp; stories', false)
            ->assertSee('/africa-mark.svg?v=dotted-20261103', false);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_public_launch_treatment_never_appears_in_staff_workspace(): void
    {
        $this->get('/staff/login')->assertOk()->assertDontSee('data-pwa-launch', false);
        $this->assertStringContainsString('sessionStorage', file_get_contents(resource_path('js/pwa.js')));
        $this->assertStringContainsString("if (installed())", file_get_contents(resource_path('js/pwa.js')));
        $this->assertStringContainsString("prefers-reduced-motion: reduce", file_get_contents(resource_path('js/pwa.js')));
    }

    public function test_icons_are_real_pngs_at_the_sizes_declared_in_the_manifest(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension required for icon resizing.');
        }

        foreach (['192' => 192, '512' => 512, '192-maskable' => 192, '512-maskable' => 512] as $name => $expected) {
            $response = $this->get('/pwa/icon/'.$name.'.png')->assertOk()->assertHeader('Content-Type', 'image/png');
            $data = getimagesizefromstring($response->getContent());
            $this->assertSame($expected, $data[0]);
            $this->assertSame($expected, $data[1]);
        }
        $this->get('/pwa/icon/400.png')->assertNotFound();
    }

    public function test_tracking_is_aggregated_without_visitors_and_invalid_events_are_rejected(): void
    {
        $this->postJson('/pwa/telemetry', ['event' => 'installed', 'platform' => 'android'])->assertStatus(202);
        $this->postJson('/pwa/telemetry', ['event' => 'installed', 'platform' => 'android'])->assertStatus(202);
        $this->postJson('/pwa/telemetry', ['event' => 'standalone_first_open', 'platform' => 'ios'])->assertStatus(202);
        $this->assertDatabaseHas('pwa_install_daily_stats', [
            'tracked_on' => now()->toDateString(), 'platform' => 'android',
            'event_type' => 'installed', 'total' => 2,
        ]);
        $this->assertDatabaseHas('pwa_install_daily_stats', [
            'tracked_on' => now()->toDateString(), 'platform' => 'ios',
            'event_type' => 'standalone_first_open', 'total' => 1,
        ]);
        $this->assertDatabaseCount('pwa_install_daily_stats', 2);

        $this->postJson('/pwa/telemetry', ['event' => 'installed', 'platform' => 'ios'])->assertUnprocessable();
        $this->postJson('/pwa/telemetry', ['event' => 'unknown', 'platform' => 'android'])->assertUnprocessable();
        $this->postJson('/pwa/telemetry', ['event' => 'installed', 'platform' => 'alien'])->assertUnprocessable();
        $this->assertDatabaseCount('pwa_install_daily_stats', 2);
    }

    public function test_install_metrics_are_visible_only_to_administrators(): void
    {
        $this->get('/staff/pwa-installs')->assertRedirect();
        $this->actingAs(User::factory()->create())->get('/staff/pwa-installs')->assertForbidden();
        $admin = User::factory()->create([
            'role' => StaffRole::SuperAdmin, 'is_active' => true, 'email_verified_at' => now(),
        ]);
        $this->actingAs($admin)->get('/staff/pwa-installs')->assertOk()
            ->assertSee('PWA installations')->assertSee('iOS first app opens');
    }

    public function test_saved_homepage_introduction_is_used_immediately_on_public_page(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin, 'is_active' => true, 'email_verified_at' => now()]);
        $this->actingAs($admin)->put('/staff/website/homepage', [
            'version' => 0, 'edition' => 'global', 'hero_title' => 'Fresh editorial homepage headline',
            'hero_highlight' => 'A new discovery experience',
            'hero_description' => 'A revised introduction for visitors.',
            'show_featured' => 1,
        ])->assertRedirect();
        $this->get('/')->assertOk()
            ->assertSee('Fresh editorial homepage headline')
            ->assertSee('A new discovery experience')
            ->assertSee('A revised introduction for visitors.');
    }
}
