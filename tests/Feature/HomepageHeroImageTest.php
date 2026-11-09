<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomepageHeroImageTest extends TestCase
{
    public function test_responsive_generated_hero_is_referenced_with_accessible_and_search_metadata(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('images/african-ranked-savannah-hero-desktop.webp')
            ->assertSee('images/african-ranked-savannah-hero-mobile.webp')
            ->assertSee('media="(max-width: 639px)"', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('loading="eager"', false)
            ->assertSee('AI-generated illustration of an acacia tree')
            ->assertSee('Hero artwork created with AI for African Ranked.')
            ->assertDontSee('Hero photography by');

        $homepageOg = app(\App\Support\Seo::class)->page(
            'home',
            app(\App\Support\Catalog::class)->region('global')
        );
        $this->assertStringEndsWith('/images/african-ranked-savannah-hero-desktop.webp', $homepageOg['image']);
    }

    public function test_homepage_hero_assets_are_real_optimized_webp_files(): void
    {
        foreach ([
            ['desktop', 1600, 900],
            ['mobile', 800, 1000],
        ] as [$variant, $width, $height]) {
            $path = public_path("images/african-ranked-savannah-hero-{$variant}.webp");
            $this->assertFileExists($path);
            $dimensions = getimagesize($path);
            $this->assertIsArray($dimensions);
            $this->assertSame($width, $dimensions[0]);
            $this->assertSame($height, $dimensions[1]);
            $this->assertSame('image/webp', $dimensions['mime']);
            $this->assertLessThan(150 * 1024, filesize($path));
        }
    }
}
