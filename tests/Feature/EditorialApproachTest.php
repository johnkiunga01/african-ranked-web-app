<?php

namespace Tests\Feature;

use App\Enums\StaffRole;
use App\Models\SitePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialApproachTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_facing_approach_has_anchors_and_real_next_steps(): void
    {
        $this->get('/strategy')->assertOk()->assertSee('Our Editorial Approach')
            ->assertSee('id="read-rankings"', false)->assertSee('id="corrections"', false)
            ->assertSee('href="/rankings"', false)->assertSee('href="/feed.xml"', false)
            ->assertDontSee('before a production launch');
    }

    public function test_published_cms_approach_uses_design_without_exposing_newer_draft(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->actingAs($admin)->post('/staff/pages', ['slug' => 'editorial-approach', 'title' => 'Our published approach', 'description' => 'Published introduction', 'body_markdown' => "## Our published method\n\nApproved CMS text.", 'in_footer' => 1])->assertRedirect();
        $page = SitePage::firstOrFail();
        $this->post('/staff/pages/'.$page->id.'/publish', ['revision_id' => $page->current_revision_id, 'confirm' => 1])->assertRedirect();
        $this->put('/staff/pages/'.$page->id, ['revision_id' => $page->current_revision_id, 'title' => 'Private approach revision', 'description' => 'Private introduction', 'body_markdown' => 'Private approach copy.', 'in_footer' => 1])->assertRedirect();
        $this->get('/strategy')->assertOk()->assertSee('Our published approach')->assertSee('Approved CMS text.')
            ->assertDontSee('Private approach copy.')->assertDontSee('Private approach revision')
            ->assertSee('aria-current="page"', false)->assertSee('Keep us accountable');
        $this->get('/pages/editorial-approach')->assertRedirect('/strategy');
        $this->post('/staff/pages/'.$page->id.'/unpublish', ['revision_id' => $page->fresh()->current_revision_id, 'confirm' => 1])->assertRedirect();
        $this->get('/strategy')->assertNotFound();
    }
}
