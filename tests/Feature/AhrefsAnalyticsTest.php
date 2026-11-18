<?php

namespace Tests\Feature;

use Tests\TestCase;

class AhrefsAnalyticsTest extends TestCase
{
    public function test_ahrefs_script_and_csp_are_absent_without_a_key(): void
    {
        config(['analytics.ahrefs_key' => '']);

        $response = $this->get('/')->assertOk()->assertDontSee('analytics.ahrefs.com', false);
        $this->assertStringNotContainsString('analytics.ahrefs.com', $response->headers->get('Content-Security-Policy'));
    }

    public function test_ahrefs_script_loads_with_key_and_scoped_csp(): void
    {
        config(['analytics.ahrefs_key' => 'TEST-KEY-123']);

        $response = $this->get('/')->assertOk()
            ->assertSee('<script src="https://analytics.ahrefs.com/analytics.js" data-key="TEST-KEY-123" async></script>', false);

        $csp = $response->headers->get('Content-Security-Policy');
        preg_match('/(?:^|;)\s*script-src\s+([^;]+)/', $csp, $script);
        preg_match('/(?:^|;)\s*connect-src\s+([^;]+)/', $csp, $connect);
        $this->assertStringContainsString('https://analytics.ahrefs.com', $script[1]);
        $this->assertStringContainsString('https://analytics.ahrefs.com', $connect[1]);
        $this->assertStringNotContainsString("'unsafe-inline'", $script[1]);
    }
}
