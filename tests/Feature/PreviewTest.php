<?php

namespace Tests\Feature;

use Tests\TestCase;

class PreviewTest extends TestCase
{
    public function test_preview_is_noindex_while_seo_markup_remains_inspectable(): void
    {
        config(['seo.preview' => true]);
        $this->get('/ke/articles/top-10-safari-destinations-africa-2026')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('rel="canonical"', false)
            ->assertSee('application/ld+json');
        $this->get('/sitemap.xml')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->get('/does-not-exist')->assertNotFound()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_review_banner_is_visible_and_html_is_not_cached(): void
    {
        config(['seo.preview' => true, 'review.enabled' => true]);
        $response = $this->get('/')->assertOk()->assertSee('Review version');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_normal_site_does_not_inherit_preview_header(): void
    {
        config(['seo.preview' => false]);
        $this->get('/')->assertOk()->assertHeaderMissing('X-Robots-Tag');
    }
}
