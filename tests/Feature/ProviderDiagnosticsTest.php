<?php

namespace Tests\Feature;

use App\Exceptions\OpenSeoConnectionException;
use App\Services\GeminiAssistant;
use App\Services\OpenSeoMcp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\TestCase;

class ProviderDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_configuration_check_is_offline_and_never_prints_credentials(): void
    {
        Http::preventStrayRequests();
        config(['services.openseo.key' => 'private-openseo', 'services.gemini.key' => 'private-gemini']);
        $this->artisan('editorial:check-providers')->expectsOutputToContain('"configured": true')
            ->doesntExpectOutputToContain('private-openseo')->doesntExpectOutputToContain('private-gemini')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_live_checks_probe_both_providers_even_if_openseo_fails(): void
    {
        config(['services.openseo.key' => 'fixture', 'services.gemini.key' => 'fixture']);
        $this->mock(OpenSeoMcp::class, function (MockInterface $mock) {
            $mock->shouldReceive('check')->once()->andThrow(OpenSeoConnectionException::forReason('authentication'));
        });
        $this->mock(GeminiAssistant::class, function (MockInterface $mock) {
            $mock->shouldReceive('generate')->once()->andReturn(['status' => 'ok']);
        });
        $this->artisan('editorial:check-providers', ['--live' => true])->expectsOutputToContain('HTTP 401')
            ->assertFailed();
        $this->assertDatabaseCount('article_revisions', 0);
    }

    public function test_live_check_verifies_project_access_and_hides_account_details(): void
    {
        config(['services.openseo.key' => 'fixture', 'services.gemini.key' => 'fixture', 'services.openseo.project_id' => 'expected']);
        $this->mock(OpenSeoMcp::class, function (MockInterface $mock) {
            $mock->shouldReceive('check')->once()->andReturn(20);
            $mock->shouldReceive('research')->with('whoami', [])->andReturn(['creditsRemaining' => 55, 'userEmail' => 'private@example.test']);
            $mock->shouldReceive('research')->with('list_projects', [])->andReturn(['projects' => [['id' => 'other']]]);
        });
        $this->mock(GeminiAssistant::class, fn (MockInterface $mock) => $mock->shouldReceive('generate')->andReturn(['status' => 'ok']));
        $this->artisan('editorial:check-providers', ['--live' => true])->expectsOutputToContain('project_not_found')
            ->doesntExpectOutputToContain('private@example.test')->assertFailed();
    }

    public function test_research_preserves_safe_authentication_diagnostic(): void
    {
        config(['services.openseo.key' => 'private-key']);
        Http::fake(['*' => Http::response('private-key upstream-private-body', 401)]);
        try {
            app(OpenSeoMcp::class)->research('whoami', []);
            $this->fail('Expected safe failure');
        } catch (OpenSeoConnectionException $exception) {
            $this->assertStringContainsString('HTTP 401', $exception->getMessage());
            $this->assertStringNotContainsString('private-key', $exception->getMessage());
            $this->assertStringNotContainsString('upstream-private-body', $exception->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_tool_credit_failure_is_classified_without_returning_private_content(): void
    {
        config(['services.openseo.key' => 'fixture']);
        Http::fake(function ($request) {
            if ($request['method'] === 'notifications/initialized') {
                return Http::response('', 202);
            }
            $result = $request['method'] === 'initialize' ? ['protocolVersion' => '2025-03-26']
                : ['isError' => true, 'content' => [['type' => 'text', 'text' => 'insufficient credits private-raw-details']]];

            return Http::response(['jsonrpc' => '2.0', 'id' => $request['id'], 'result' => $result]);
        });
        try {
            app(OpenSeoMcp::class)->research('get_serp_results', []);
            $this->fail('Expected refusal');
        } catch (OpenSeoConnectionException $exception) {
            $this->assertStringContainsString('credit or quota', $exception->getMessage());
            $this->assertStringNotContainsString('private-raw-details', $exception->getMessage());
        }
    }
}
