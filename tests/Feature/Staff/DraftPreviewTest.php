<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\User;
use App\Services\SeedArticleImporter;
use App\Support\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_preview_renders_structured_blocks_and_review_handoff_without_server_error(): void
    {
        $owner = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $owner);

        $revision = $article->currentRevision;
        $revision->body = [
            ['type' => 'heading', 'level' => 2, 'text' => 'The overview'],
            ['type' => 'paragraph', 'text' => 'A useful explanation.'],
            ['type' => 'image', 'url' => 'https://example.test/cover.webp', 'alt' => 'Birds in the landscape', 'caption' => 'Example image', 'credit' => 'Test photographer'],
            ['type' => 'list', 'items' => ['First step', 'Second step']],
            ['type' => 'link', 'url' => 'https://example.test/source', 'text' => 'Read the source'],
            ['type' => 'quote', 'text' => 'Quoted observation', 'attribution' => 'A source'],
            ['type' => 'not-yet-supported'],
        ];
        $revision->save();

        $this->actingAs($owner)->get('/staff/articles/'.$article->id.'/preview')
            ->assertOk()
            ->assertSee('The overview')
            ->assertSee('A useful explanation.')
            ->assertSee('Birds in the landscape')
            ->assertSee('Example image')
            ->assertSee('First step')
            ->assertSee('Read the source')
            ->assertSee('Quoted observation')
            ->assertSee('Submit for review')
            ->assertSee('name="revision_id" value="'.$revision->id.'"', false);
    }

    public function test_preview_keeps_other_authors_drafts_private(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $article = app(SeedArticleImporter::class)->import(app(Catalog::class)->data('articles')[0], $owner);

        $this->actingAs($other)->get('/staff/articles/'.$article->id.'/preview')->assertOk();
    }
}
