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

    public function test_regional_pages_use_distinct_meta_descriptions_and_category_cards_have_h2_parent(): void
    {
        $homeDescriptions = [];
        $travelDescriptions = [];

        foreach (['global' => '', 'us' => '/us', 'uk' => '/uk', 'ke' => '/ke'] as $code => $prefix) {
            $home = $this->get($prefix ?: '/')->assertOk();
            $travel = $this->get($prefix.'/categories/travel')->assertOk();

            $this->assertSame(1, preg_match('/<meta name="description" content="([^"]+)"/', $home->getContent(), $homeMatch));
            $this->assertSame(1, preg_match('/<meta name="description" content="([^"]+)"/', $travel->getContent(), $travelMatch));
            $homeDescriptions[$code] = $homeMatch[1];
            $travelDescriptions[$code] = $travelMatch[1];

            // h1 category masthead -> h2 articles section -> h3 linked article cards.
            $travel->assertSee('id="category-articles-heading"', false)
                ->assertSee('<h2 id="category-articles-heading"', false);
        }

        $this->assertCount(4, array_unique($homeDescriptions));
        $this->assertCount(4, array_unique($travelDescriptions));
        $this->assertStringContainsString('United States edition', $travelDescriptions['us']);
        $this->assertStringContainsString('Kenya edition', $homeDescriptions['ke']);
    }

}
