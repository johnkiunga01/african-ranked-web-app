<?php

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Models\User;
use App\Services\LaunchReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LaunchHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_and_schema_nonce_are_present_without_script_unsafe_inline(): void
    {
        $response = $this->get('/')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY');
        $policy = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        preg_match('/script-src ([^;]+)/', $policy, $scripts);
        $this->assertStringNotContainsString("'unsafe-inline'", $scripts[1]);
        preg_match("/'nonce-([^']+)'/", $scripts[1], $nonce);
        $response->assertSee('nonce="'.$nonce[1].'"', false);
        $second = $this->get('/')->assertOk();
        $this->assertNotSame($policy, $second->headers->get('Content-Security-Policy'));
    }

    public function test_readiness_reports_review_blockers_and_no_secrets_without_changing_environment(): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32)), 'review.enabled' => true, 'seo.preview' => true]);
        $report = app(LaunchReadiness::class)->report();
        $this->assertLessThan($report['total'], $report['passed']);
        $this->assertStringNotContainsString(config('app.key'), json_encode($report));
        $this->artisan('launch:check', ['--json' => true])->assertExitCode(1);
        $this->assertTrue(config('review.enabled'));
        $this->assertTrue(config('seo.preview'));
        $this->actingAs(User::factory()->create())->get('/staff/launch-readiness')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => StaffRole::SuperAdmin]))->get('/staff/launch-readiness')->assertOk()->assertSee('Manual sign-offs');
    }

    public function test_production_rejects_unrecognized_host_before_password_reset_and_emits_hsts(): void
    {
        Notification::fake();
        $original = $this->app['env'];
        $this->app['env'] = 'production';
        config(['app.url' => 'https://publisher.example.org']);
        try {
            $this->get('https://unrecognized.example/up')->assertOk();
            $this->post('https://unrecognized.example/staff/forgot-password', ['email' => 'owner@example.test'])->assertStatus(400);
            Notification::assertNothingSent();
            $this->get('https://publisher.example.org/staff/login')->assertOk()->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        } finally {
            $this->app['env'] = $original;
        }
    }
}
