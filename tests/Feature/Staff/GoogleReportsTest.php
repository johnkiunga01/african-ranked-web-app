<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleReportsTest extends TestCase
{
    use RefreshDatabase;

    private function configure(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        config(['services.google_reports' => [
            'credentials' => base64_encode(json_encode(['client_email' => 'reports@example.iam.gserviceaccount.com', 'private_key' => $pem])),
            'ga4_property_id' => '123456789',
            'search_console_site' => 'sc-domain:africanranked.com',
            'cache_minutes' => 60,
        ]]);
        Cache::flush();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => StaffRole::SuperAdmin]);
    }

    public function test_pages_explain_setup_when_google_is_not_configured(): void
    {
        config(['services.google_reports.credentials' => null]);
        Http::fake();
        $this->actingAs($this->admin());
        $this->get('/staff/growth/analytics')->assertOk()->assertSee('Connect Google Analytics to see live reports here');
        $this->get('/staff/growth/seo')->assertOk()->assertSee('Connect Search Console to see live reports here');
        Http::assertNothingSent();
    }

    public function test_analytics_page_shows_live_ga4_report(): void
    {
        $this->configure();
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'test-token', 'expires_in' => 3600]),
            'analyticsdata.googleapis.com/*' => function ($request) {
                $dims = array_column($request['dimensions'] ?? [], 'name');
                if ($dims === []) {
                    return Http::response(['rows' => [
                        ['metricValues' => [['value' => '1200'], ['value' => '1500'], ['value' => '4100'], ['value' => '0.61'], ['value' => '95']]],
                        ['metricValues' => [['value' => '1000'], ['value' => '1300'], ['value' => '3500'], ['value' => '0.58'], ['value' => '80']]],
                    ]]);
                }
                $value = $dims[0] === 'pagePath' ? '/articles/kruger-vs-masai-mara' : ($dims[0] === 'date' ? '20261101' : 'Organic Search');
                $metrics = array_fill(0, count($request['metrics']), ['value' => '42']);

                return Http::response(['rows' => [['dimensionValues' => [['value' => $value]], 'metricValues' => $metrics]]]);
            },
        ]);
        $this->actingAs($this->admin());
        $this->get('/staff/growth/analytics')->assertOk()
            ->assertSee('Google Analytics 4 · Live')->assertSee('1,200')->assertSee('20.0%')
            ->assertSee('/articles/kruger-vs-masai-mara')->assertSee('Organic Search')
            ->assertDontSee('test-token');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'properties/123456789:runReport') && $r->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_seo_page_shows_live_search_console_report_and_is_cached(): void
    {
        $this->configure();
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'test-token']),
            'www.googleapis.com/webmasters/*' => function ($request) {
                $dim = $request['dimensions'][0] ?? null;
                $key = match ($dim) { 'query' => 'best safari in africa', 'page' => 'https://africanranked.com/articles/top-10-safari-destinations-africa-2026', 'date' => '2026-11-01', default => null };

                return Http::response(['rows' => [['keys' => $key ? [$key] : [], 'clicks' => 30, 'impressions' => 900, 'ctr' => 0.0333, 'position' => 14.2]]]);
            },
        ]);
        $this->actingAs($this->admin());
        $this->get('/staff/growth/seo')->assertOk()->assertSee('Google Search Console · Live')->assertSee('best safari in africa')->assertSee('900')->assertSee('14.2');
        $sent = count(Http::recorded());
        $this->get('/staff/growth/seo')->assertOk();
        $this->assertCount($sent, Http::recorded());
        Http::assertSent(fn ($r) => str_contains($r->url(), 'sites/sc-domain%3Aafricanranked.com/searchAnalytics/query'));
    }

    public function test_google_errors_are_sanitized(): void
    {
        $this->configure();
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'test-token']),
            'analyticsdata.googleapis.com/*' => Http::response(['error' => ['message' => 'secret internal detail']], 403),
        ]);
        $this->actingAs($this->admin());
        $this->get('/staff/growth/analytics')->assertOk()
            ->assertSee('Google Analytics denied access')->assertSee('reports@example.iam.gserviceaccount.com')
            ->assertDontSee('secret internal detail');
    }

    public function test_refresh_requires_admin_and_clears_cache(): void
    {
        $this->configure();
        Cache::put('google_reports.ga4', ['ok' => true, 'data' => []], 60);
        $this->actingAs(User::factory()->create());
        $this->post('/staff/growth/google-reports/refresh', ['section' => 'analytics'])->assertForbidden();
        $this->assertTrue(Cache::has('google_reports.ga4'));
        $this->actingAs($this->admin());
        $this->post('/staff/growth/google-reports/refresh', ['section' => 'analytics'])->assertRedirect('/staff/growth/analytics');
        $this->assertFalse(Cache::has('google_reports.ga4'));
    }
}
