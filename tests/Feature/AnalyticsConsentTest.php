<?php

namespace Tests\Feature;

use Tests\TestCase;

class AnalyticsConsentTest extends TestCase
{
    public function test_ga4_tag_is_absent_by_default(): void
    {
        config(['analytics.enabled' => false, 'analytics.measurement_id' => 'G-EXAMPLE123']);

        $this->get('/')->assertOk()
            ->assertDontSee('data-analytics-consent', false)
            ->assertDontSee('www.googletagmanager.com/gtag/js', false);
    }

    public function test_consent_gated_ga4_markup_uses_nonce_and_scoped_csp(): void
    {
        config(['analytics.enabled' => true, 'analytics.measurement_id' => 'G-EXAMPLE123']);

        $response = $this->get('/')->assertOk()
            ->assertSee('data-analytics-consent', false)
            ->assertSee('data-analytics-choice="reject"', false)
            ->assertSee('data-analytics-choice="accept"', false)
            ->assertSee('/pages/privacy-policy', false)
            ->assertSee('window.localStorage.getItem', false);

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('https://www.googletagmanager.com', $csp);
        $this->assertStringContainsString('https://www.google-analytics.com', $csp);
        preg_match('/(?:^|;)\s*script-src\s+([^;]+)/', $csp, $scriptDirective);
        $this->assertStringNotContainsString("'unsafe-inline'", $scriptDirective[1]);

        config(['analytics.enabled' => false]);
        $public = $this->get('/')->assertOk();
        $this->assertStringNotContainsString('www.googletagmanager.com', $public->headers->get('Content-Security-Policy'));
    }
}
