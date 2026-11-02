<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Exceptions\OpenSeoConnectionException;
use App\Models\SearchReport;
use App\Models\User;
use App\Services\OpenSeoMcp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $rows = "https://example.test/a,3,100,3%,4.2\n"): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('Pages.csv', "Top pages,Clicks,Impressions,CTR,Position\n".$rows);
    }

    public function test_search_console_import_is_private_validated_deduplicated_and_reports_real_exported_totals(): void
    {
        $data = ['property' => 'sc-domain:example.test', 'period_start' => '2026-01-01', 'period_end' => '2026-01-31', 'report' => $this->csv()];
        $this->actingAs(User::factory()->create())->post('/staff/search-reports', $data)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => StaffRole::SuperAdmin]));
        $this->post('/staff/search-reports', $data)->assertRedirect('/staff/confirm-password');
        $this->withSession(['auth.password_confirmed_at' => time()]);
        $this->post('/staff/search-reports', $data)->assertRedirect('/staff/growth/analytics');
        $this->post('/staff/search-reports', $data)->assertRedirect();
        $this->assertDatabaseCount('search_reports', 1);
        $this->assertSame(3, SearchReport::first()->clicks);
        $this->assertSame(100, SearchReport::first()->impressions);
        $this->get('/staff/growth/analytics')->assertOk()->assertSee('https://example.test/a')->assertSee('Manual CSV import');
        $data['report'] = $this->csv("javascript:alert(1),1,10,10%,1\n");
        $this->post('/staff/search-reports', $data)->assertSessionHasErrors('report');
        $data['report'] = $this->csv("https://example.test/a,20,10,10%,1\n");
        $this->post('/staff/search-reports', $data)->assertSessionHasErrors('report');
        $this->assertDatabaseCount('search_reports', 1);
    }

    public function test_mcp_negotiates_session_discovers_tools_and_calls_only_free_read_functions(): void
    {
        config(['services.openseo.key' => 'fake-test-only-key']);
        $calls = [];
        Http::fake(function ($request) use (&$calls) {
            $calls[] = $request->data();
            $method = $request['method'];
            if ($method !== 'initialize') {
                $this->assertTrue($request->hasHeader('Mcp-Session-Id', 'fixture-session'));
            }
            if ($method === 'notifications/initialized') {
                return Http::response('', 202);
            }
            $result = match ($method) {
                'initialize' => ['protocolVersion' => '2025-03-26', 'capabilities' => (object) []],
                'tools/list' => ['tools' => [['name' => 'whoami'], ['name' => 'list_projects'], ['name' => 'paid_keyword_tool']]],
                'tools/call' => ['content' => [['type' => 'text', 'text' => 'fixture private account result']]],
            };
            $payload = ['jsonrpc' => '2.0', 'id' => $request['id'], 'result' => $result];

            return Http::response('event: message'."\n".'data: '.json_encode($payload)."\n\n", 200, ['Content-Type' => 'text/event-stream', 'Mcp-Session-Id' => 'fixture-session']);
        });
        $this->assertSame(3, app(OpenSeoMcp::class)->check());
        $toolCalls = collect($calls)->filter(fn ($c) => $c['method'] === 'tools/call')->pluck('params.name')->all();
        $this->assertSame(['whoami', 'list_projects'], $toolCalls);
        Http::assertSent(fn ($request) => $request->url() === 'https://app.openseo.so/mcp' && $request->hasHeader('Authorization', 'Bearer fake-test-only-key'));
    }

    public function test_mcp_failures_do_not_expose_provider_responses_or_credentials(): void
    {
        config(['services.openseo.key' => 'fake-sensitive-key']);
        Http::fake(['*' => Http::response('fake-sensitive-key provider-private-content', 401)]);
        $this->actingAs(User::factory()->create(['role' => StaffRole::SuperAdmin]))->withSession(['auth.password_confirmed_at' => time()]);
        $this->from('/staff/growth/integrations')->post('/staff/openseo/check')->assertRedirect()->assertSessionHasErrors('openseo');
        $this->get('/staff/growth/integrations')->assertDontSee('fake-sensitive-key')->assertDontSee('provider-private-content');
        $this->assertDatabaseHas('integration_checks', ['provider' => 'openseo', 'status' => 'failed']);
    }

    public function test_connection_failures_are_specific_without_leaking_upstream_details(): void
    {
        config(['services.openseo.key' => 'fixture-private-credential']);
        foreach ([401 => 'HTTP 401', 403 => 'HTTP 403', 429 => 'HTTP 429', 503 => 'temporarily unavailable'] as $status => $expected) {
            Http::swap(new Factory);
            Http::fake(['*' => Http::response('upstream-private-content', $status)]);
            try {
                app(OpenSeoMcp::class)->check();
                $this->fail('A failed provider request must not verify the connection.');
            } catch (OpenSeoConnectionException $exception) {
                $this->assertStringContainsString($expected, $exception->getMessage());
                $this->assertStringNotContainsString('upstream-private-content', $exception->getMessage());
                $this->assertStringNotContainsString('fixture-private-credential', $exception->getMessage());
            }
        }
        Http::swap(new Factory);
        Http::fake(fn () => throw new ConnectionException('private-transport-details'));
        try {
            app(OpenSeoMcp::class)->check();
            $this->fail('A network failure must not verify the connection.');
        } catch (OpenSeoConnectionException $exception) {
            $this->assertStringContainsString('HTTPS connection', $exception->getMessage());
            $this->assertStringNotContainsString('private-transport-details', $exception->getMessage());
        }
    }
}
