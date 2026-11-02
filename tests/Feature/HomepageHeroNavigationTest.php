<?php

namespace Tests\Feature;

use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageHeroNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_hero_button_opens_a_real_rankings_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="/rankings"', false)
            ->assertSee('href="#categories"', false)
            ->assertSee('id="categories"', false)
            ->assertSee('Explore Africa’s best.')
            ->assertSee('Go beyond the list.');

        $this->get('/rankings')
            ->assertOk()
            ->assertSee('African rankings &amp; guides', false)
            ->assertSee('Only published articles appear here.');
    }

    public function test_regional_hero_uses_matching_regional_rankings_and_category_section(): void
    {
        $this->get('/ke')
            ->assertOk()
            ->assertSee('href="/ke/rankings"', false)
            ->assertSee('href="#categories"', false)
            ->assertSee('id="categories"', false);

        $this->get('/ke/rankings')
            ->assertOk()
            ->assertSee('Kenya edition')
            ->assertSee('African rankings &amp; guides', false);
    }

    public function test_legacy_hero_text_is_upgraded_without_overwriting_custom_copy(): void
    {
        $settings = app(SiteSettings::class);
        $this->assertSame('Explore Africa’s best.', $settings->snapshot('homepage')['value']['hero_title']);
        $this->assertSame('Go beyond the list.', $settings->snapshot('homepage')['value']['hero_highlight']);
    }
}
