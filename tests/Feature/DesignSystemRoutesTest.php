<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSystemRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_immersive_homepage_uses_real_navigation_and_six_category_destinations(): void
    {
        $page = $this->get('/')->assertOk()
            ->assertSee('Discover the best of Africa')
            ->assertSee('through rankings and guides.')
            ->assertSee('class="ar-hero', false)
            ->assertSee('href="/rankings"', false)
            ->assertSee('href="#categories"', false)
            ->assertSee('id="categories"', false)
            ->assertDontSee('Review version')
            ->assertDontSee('Coming soon');

        foreach (['wildlife', 'travel', 'food', 'business', 'tech', 'music'] as $slug) {
            $page->assertSee('href="/categories/'.$slug.'"', false);
            $this->get('/categories/'.$slug)->assertOk();
        }

        $this->get('/rankings')->assertOk();
    }

    public function test_search_guide_filter_and_regional_navigation_return_real_pages(): void
    {
        $this->get('/rankings?q=safari')->assertOk()->assertSee('value="safari"', false);
        $this->get('/rankings?type=guide')->assertOk()->assertSee('Practical African guides');
        $this->get('/ke')->assertOk()->assertSee('href="/ke/rankings"', false);
        $this->get('/ke/rankings?q=wildlife')->assertOk()->assertSee('value="wildlife"', false);
    }

    public function test_search_validation_rejects_oversized_queries(): void
    {
        $this->get('/rankings?q='.str_repeat('z', 101))->assertSessionHasErrors('q');
    }
}
