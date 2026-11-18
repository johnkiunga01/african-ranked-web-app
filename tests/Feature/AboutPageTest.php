<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_is_discoverable_and_has_its_own_canonical(): void
    {
        $response = $this->get('/about')->assertOk()
            ->assertSee('A continent of stories.')
            ->assertSee('info@africanranked.com')
            ->assertSee('href="/about"', false)
            ->assertSee('aria-current="page"', false);
        $this->assertStringContainsString('/about', $response->getContent());
        $this->get('/')->assertOk()->assertSee('About us');
    }
}
