<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Services\PublishingGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishingGateTest extends TestCase
{
    use RefreshDatabase;

    private function thinTop10(): Article
    {
        $cat = Category::create(['slug' => 'wildlife', 'name' => 'Wildlife & Safaris']);
        $author = Author::create(['name' => 'A Writer', 'slug' => 'a-writer', 'bio' => 'Travel writer.']);

        return Article::create([
            'type' => 'ranking', 'status' => 'draft', 'slug' => 'top-10-safari', 'edition' => 'global',
            'category_id' => $cat->id, 'author_id' => $author->id,
            'title' => 'Top 10 Safari Destinations in Africa for 2026',
            'answer_block' => 'Too short.',
            'body' => 'A short body that is nowhere near the required length. [TBD]',
        ]);
    }

    public function test_thin_article_cannot_publish(): void
    {
        $failures = app(PublishingGate::class)->evaluate($this->thinTop10());

        $this->assertNotEmpty($failures);
        $this->assertTrue(collect($failures)->contains(fn ($f) => str_contains($f, 'minimum is 1300')));
        $this->assertTrue(collect($failures)->contains(fn ($f) => str_contains($f, 'promises Top 10')));
        $this->assertTrue(collect($failures)->contains(fn ($f) => str_contains($f, 'Placeholder text')));
    }

    public function test_publish_throws_when_gates_fail(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->thinTop10()->publish();
    }

    public function test_robots_blocks_everything_outside_production(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /', false);
    }

    public function test_llms_txt_states_positioning(): void
    {
        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee('independent reference site that ranks and compares the best of Africa', false);
    }
}
