<?php

namespace Tests\Feature\Staff;

use App\Enums\RevisionStatus;
use App\Enums\StaffRole;
use App\Jobs\CreateEditorialPlanDraft;
use App\Models\Article;
use App\Models\EditorialBrief;
use App\Models\EditorialDraftBatchItem;
use App\Models\User;
use App\Services\EditorialDraftBatchService;
use App\Services\EditorialPlanImport;
use App\Services\EditorialWorkbook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use ZipArchive;

class EditorialPlanTest extends TestCase
{
    use RefreshDatabase;

    private function workbook(bool $formula = false): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ar-xlsx-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('xl/workbook.xml', '<workbook xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Article Ideas" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships><Relationship Id="rId1" Target="worksheets/sheet1.xml"/></Relationships>');
        $headers = ['ID', 'Wave', 'Category', 'Template', 'CMS type', 'Working title', 'Slug', 'Primary keyword', 'Target body words', 'Key sources to cite', 'Data risk', 'Pillar', 'Internal links (IDs)', 'Recommended structure (H2 order)'];
        $values = ['AR-001', '1', 'Wildlife & Safaris', 'T2 Guide', 'guide', 'A real planning question', 'a-real-planning-question', 'planning a safari', '1600', 'Park authorities', 'Medium', 'Safari planning', 'AR-002', 'Context > Decision framework'];
        $xml = '<worksheet><sheetData>';
        foreach ([1 => $headers, 2 => $values] as $number => $row) {
            $xml .= '<row r="'.$number.'">';
            foreach ($row as $i => $value) {
                $xml .= '<c r="'.chr(65 + $i).$number.'" t="inlineStr">'.($formula && $number === 2 && $i === 0 ? '<f>HYPERLINK("https://invalid.example")</f>' : '').'<is><t>'.htmlspecialchars($value, ENT_XML1).'</t></is></c>';
            }
            $xml .= '</row>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml.'</sheetData></worksheet>');
        $zip->close();

        return $path;
    }

    public function test_workbook_mapping_preserves_identifiers_and_real_row_provenance_without_provider_calls(): void
    {
        Http::preventStrayRequests();
        $path = $this->workbook();
        try {
            $intake = app(EditorialWorkbook::class)->read($path, 'master.xlsx');
            $this->assertSame('AR-001', $intake['records'][0]['plan_id']);
            $this->assertSame(2, $intake['records'][0]['plan']['row']);
            $this->assertSame('wildlife', $intake['records'][0]['category_slug']);
            $this->assertSame(0, $intake['drafts']);
            $this->assertDatabaseCount('articles', 0);
        } finally {
            unlink($path);
        }
    }

    public function test_formula_in_plan_is_rejected_without_evaluation(): void
    {
        $path = $this->workbook(true);
        try {
            $this->expectException(ValidationException::class);
            app(EditorialWorkbook::class)->read($path, 'formula.xlsx');
        } finally {
            unlink($path);
        }
    }

    public function test_import_is_private_idempotent_and_preserves_team_drafts_when_master_is_reimported(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $path = $this->workbook();
        try {
            $intake = app(EditorialWorkbook::class)->read($path, 'master.xlsx');
            $team = $intake;
            $team['records'][0]['plan']['team_draft'] = 'A team-authored draft to preserve.';
            $importer = app(EditorialPlanImport::class);
            $importer->commit($admin, $team);
            $importer->commit($admin, $intake);
            $this->assertDatabaseCount('editorial_briefs', 1);
            $this->assertDatabaseCount('articles', 0);
            $brief = EditorialBrief::firstOrFail();
            $this->assertSame('A team-authored draft to preserve.', $brief->plan['team_draft']);
            $article = $importer->start($admin, $brief);
            $this->assertNull($article->published_revision_id);
            $this->assertSame('A team-authored draft to preserve.', $article->currentRevision->body_markdown);
            $this->assertSame($article->id, $importer->start($admin, $brief)->id);
            $this->assertSame(1, $article->revisions()->count());
            $this->assertSame(1, Article::count());
            $this->get('/articles/'.$article->slug)->assertNotFound();
        } finally {
            unlink($path);
        }
    }

    public function test_upload_redirects_to_get_preview_and_import_remains_private(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $path = $this->workbook();
        try {
            $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
                ->post(route('staff.editorial-plan.preview'), [
                    'workbook' => new UploadedFile($path, 'master.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
                ])->assertStatus(303)->assertRedirect(route('staff.editorial-plan.preview.show'));

            $this->get(route('staff.editorial-plan.preview.show'))
                ->assertOk()
                ->assertSee('A real planning question')
                ->assertHeader('Cache-Control', 'no-store, private');

            $this->post(route('staff.editorial-plan.commit'), ['confirm_private' => 1])
                ->assertRedirect(route('staff.editorial-plan'));

            $this->assertDatabaseCount('editorial_briefs', 1);
            $this->assertDatabaseCount('articles', 0);
        } finally {
            unlink($path);
        }
    }

    public function test_import_validation_error_returns_to_a_readable_preview_instead_of_405(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $path = $this->workbook();
        try {
            $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
                ->post(route('staff.editorial-plan.preview'), [
                    'workbook' => new UploadedFile($path, 'master.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
                ])->assertStatus(303);

            $this->get(route('staff.editorial-plan.preview.show'))->assertOk();

            $this->from(route('staff.editorial-plan.preview.show'))
                ->post(route('staff.editorial-plan.commit'), ['confirm_private' => '0'])
                ->assertRedirect(route('staff.editorial-plan.preview.show'));

            $this->get(route('staff.editorial-plan.preview.show'))
                ->assertOk()
                ->assertSee('The confirm private field must be accepted.');

            $this->assertDatabaseCount('editorial_briefs', 0);
            $this->assertDatabaseCount('articles', 0);
        } finally {
            unlink($path);
        }
    }

    public function test_direct_or_expired_preview_get_recovers_to_editorial_plan(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
        $this->get(route('staff.editorial-plan.preview.show'))
            ->assertRedirect(route('staff.editorial-plan'));
    }

    public function test_phase_two_workbook_reuses_existing_slug_without_overwriting_manuscript_or_article(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $path = $this->workbook();
        try {
            $importer = app(EditorialPlanImport::class);
            $original = app(EditorialWorkbook::class)->read($path, 'first-phase.xlsx');
            $original['records'][0]['plan']['team_draft'] = 'Original human-edited team draft.';
            $importer->commit($admin, $original);
            $oldBrief = EditorialBrief::firstOrFail();
            $article = $importer->start($admin, $oldBrief);
            $currentRevision = $article->current_revision_id;

            $phaseTwo = $original;
            $phaseTwo['records'][0]['plan_id'] = 'AR-26001';
            $phaseTwo['records'][0]['title'] = 'Phase two improved safari planning';
            $phaseTwo['records'][0]['keyword'] = 'semrush-researched safari intent';
            $phaseTwo['records'][0]['plan']['team_draft'] = 'Replacement that must not overwrite the original manuscript.';
            $phaseTwo['hash'] = 'phase-two-test';

            $inspection = $importer->inspect($phaseTwo['records']);
            $this->assertSame(1, $inspection['counts']['reused_slug']);
            $this->assertSame('AR-001', $inspection['items'][0]['matched_id']);

            $this->assertSame(1, $importer->commit($admin, $phaseTwo)['reused_slug']);
            $this->assertSame(1, $importer->commit($admin, $phaseTwo)['reused_slug']);
            $this->assertDatabaseCount('editorial_briefs', 1);
            $this->assertDatabaseCount('articles', 1);

            $brief = EditorialBrief::firstOrFail()->refresh();
            $this->assertSame('AR-001', $brief->plan_id);
            $this->assertSame('AR-26001', $brief->plan['source_workbook_plan_id']);
            $this->assertSame('Phase two improved safari planning', $brief->title);
            $this->assertSame('semrush-researched safari intent', $brief->keyword);
            $this->assertSame('Original human-edited team draft.', $brief->plan['team_draft']);
            $this->assertSame($article->id, $brief->article_id);
            $this->assertSame($article->id, $importer->start($admin, $brief)->id);
            $this->assertSame($currentRevision, $article->fresh()->current_revision_id);
            $this->assertNull($article->fresh()->published_revision_id);
            $this->assertSame(1, $article->revisions()->count());
        } finally {
            unlink($path);
        }
    }

    public function test_reused_plan_id_for_different_slug_is_rejected_atomically(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $path = $this->workbook();
        try {
            $importer = app(EditorialPlanImport::class);
            $original = app(EditorialWorkbook::class)->read($path, 'first-phase.xlsx');
            $importer->commit($admin, $original);

            $new = $original['records'][0];
            $new['plan_id'] = 'AR-26001';
            $new['slug'] = 'a-completely-new-safari-topic';
            $new['title'] = 'New and unrelated topic';

            $conflicting = $original['records'][0];
            $conflicting['slug'] = 'different-slug-than-original';
            $conflicting['title'] = 'Dangerous ID reuse';

            $incoming = ['records' => [$new, $conflicting], 'hash' => 'conflict-test', 'drafts' => 0];
            $this->assertSame(1, $importer->inspect($incoming['records'])['counts']['conflict']);

            try {
                $importer->commit($admin, $incoming);
                $this->fail('The import should reject a plan ID assigned to another slug.');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('AR-001', $e->errors()['workbook'][0]);
            }

            $this->assertDatabaseCount('editorial_briefs', 1);
            $this->assertDatabaseMissing('editorial_briefs', ['plan_id' => 'AR-26001']);
            $this->assertDatabaseHas('editorial_briefs', ['plan_id' => 'AR-001', 'slug' => 'a-real-planning-question']);
        } finally {
            unlink($path);
        }
    }

    public function test_phase_two_research_does_not_claim_an_article_already_owned_by_another_brief(): void
    {
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $path = $this->workbook();
        try {
            $importer = app(EditorialPlanImport::class);
            $phaseOne = app(EditorialWorkbook::class)->read($path, 'first-phase.xlsx');
            $phaseOne['records'][0]['plan']['team_draft'] = 'Original human-written content remains untouched.';
            $importer->commit($admin, $phaseOne);

            $owner = EditorialBrief::firstOrFail();
            $article = $importer->start($admin, $owner);
            $currentRevision = $article->current_revision_id;

            // Historic master guide brief references the same article using
            // another planning slug. The actual article slug is still unchanged.
            $owner->update(['slug' => 'historic-safari-research-brief']);

            $phaseTwo = $phaseOne;
            $phaseTwo['records'][0]['plan_id'] = 'AR-26064';
            $phaseTwo['records'][0]['plan']['team_draft'] = 'Unverified Phase 2 Semrush research brief.';
            $phaseTwo['hash'] = 'phase-two-owned-article';

            $inspection = $importer->inspect($phaseTwo['records']);
            $this->assertSame(1, $inspection['counts']['linked_elsewhere']);
            $this->assertSame('AR-001', $inspection['items'][0]['matched_id']);

            $result = $importer->commit($admin, $phaseTwo);
            $this->assertSame(1, $result['linked_elsewhere']);
            $this->assertSame(1, $importer->commit($admin, $phaseTwo)['existing']);

            $this->assertDatabaseCount('editorial_briefs', 2);
            $this->assertDatabaseCount('articles', 1);
            $this->assertSame($article->id, $owner->fresh()->article_id);
            $this->assertSame('Original human-written content remains untouched.', $owner->fresh()->plan['team_draft']);

            $research = EditorialBrief::where('plan_id', 'AR-26064')->firstOrFail();
            $this->assertNull($research->article_id);
            $this->assertSame($article->id, $research->plan['related_existing_article_id']);
            $this->assertSame('AR-001', $research->plan['related_existing_plan_id']);
            $this->assertSame('Unverified Phase 2 Semrush research brief.', $research->plan['team_draft']);
            $this->assertSame($currentRevision, $article->fresh()->current_revision_id);
            $this->assertNull($article->fresh()->published_revision_id);
            $this->assertSame(1, $article->revisions()->count());

            $this->actingAs($admin)->get(route('staff.editorial-plan'))
                ->assertOk()
                ->assertSee('Research brief only; the existing CMS article belongs to plan AR-001.');
        } finally {
            unlink($path);
        }
    }

    public function test_bulk_private_drafts_are_queued_and_created_only_after_worker_runs(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $path = $this->workbook();
        try {
            $intake = app(EditorialWorkbook::class)->read($path, 'phase-two.xlsx');
            $intake['records'][0]['plan']['team_draft'] = '# A private research brief'."\n\nResearch to verify before review.";
            $intake['records'][0]['plan']['fields']['Deduplication decision'] = 'CREATE_IF_UNIQUE';
            app(EditorialPlanImport::class)->commit($admin, $intake);
            $service = app(EditorialDraftBatchService::class);
            $preview = $service->inspect($intake['hash']);
            $this->assertSame(1, $preview['counts']['ready']);
            $this->assertDatabaseCount('articles', 0);

            $batch = $service->queue($admin, $intake['hash']);
            $this->assertSame(1, $batch->total);
            Queue::assertPushed(CreateEditorialPlanDraft::class, 1);
            $this->assertDatabaseCount('articles', 0);

            $this->actingAs($admin)->get(route('staff.editorial-plan.draft-batches.show', $batch))
                ->assertOk()->assertSee('Bulk private draft creation');
            $this->getJson(route('staff.editorial-plan.draft-batches.show', $batch))
                ->assertOk()->assertJsonPath('remaining', 1);

            $item = $batch->items()->firstOrFail();
            (new CreateEditorialPlanDraft($item->id))->handle(app(EditorialPlanImport::class), $service);
            $item->refresh();
            $this->assertSame('created', $item->status);
            $this->assertDatabaseCount('articles', 1);
            $article = $item->article;
            $this->assertSame(RevisionStatus::Draft, $article->currentRevision->status);
            $this->assertNull($article->published_revision_id);
            $this->assertSame(1, $article->revisions()->count());

            (new CreateEditorialPlanDraft($item->id))->handle(app(EditorialPlanImport::class), $service);
            $this->assertSame(1, $article->revisions()->count());
            $this->getJson(route('staff.editorial-plan.draft-batches.show', $batch))
                ->assertOk()->assertJsonPath('counts.created', 1)->assertJsonPath('complete', true);
            $this->assertSame(0, $service->inspect($intake['hash'])['counts']['ready']);
        } finally {
            unlink($path);
        }
    }

    public function test_bulk_queue_skips_held_duplicates_existing_links_and_related_article_research(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $path = $this->workbook();
        try {
            $intake = app(EditorialWorkbook::class)->read($path, 'phase-two.xlsx');
            $base = $intake['records'][0];
            $base['plan']['team_draft'] = 'A private outline.';
            $base['plan']['fields']['Deduplication decision'] = 'CREATE_IF_UNIQUE';

            $held = $base;
            $held['plan_id'] = 'AR-002';
            $held['slug'] = 'overlapping-safari-topic';
            $held['plan']['team_status'] = 'Hold — duplicate topic';

            $related = $base;
            $related['plan_id'] = 'AR-003';
            $related['slug'] = 'safari-research-with-existing-link';
            $related['plan']['related_existing_article_id'] = 123;

            $noDraft = $base;
            $noDraft['plan_id'] = 'AR-004';
            $noDraft['slug'] = 'another-safari-topic';
            $noDraft['plan']['team_draft'] = '';

            $intake['records'] = [$base, $held, $related, $noDraft];
            $importer = app(EditorialPlanImport::class);
            $importer->commit($admin, $intake);

            $service = app(EditorialDraftBatchService::class);
            $inspection = $service->inspect($intake['hash']);
            $this->assertSame(1, $inspection['counts']['ready']);
            $this->assertSame(1, $inspection['counts']['hold']);
            $this->assertSame(1, $inspection['counts']['related']);
            $this->assertSame(1, $inspection['counts']['no_draft']);

            $batch = $service->queue($admin, $intake['hash']);
            $this->assertSame(1, $batch->total);
            Queue::assertPushed(CreateEditorialPlanDraft::class, 1);
            $this->assertDatabaseCount('editorial_draft_batch_items', 1);
            $this->assertDatabaseCount('articles', 0);

            $again = $service->queue($admin, $intake['hash']);
            $this->assertSame($batch->id, $again->id);
            $this->assertDatabaseCount('editorial_draft_batches', 1);
            Queue::assertPushed(CreateEditorialPlanDraft::class, 1);
        } finally {
            unlink($path);
        }
    }

    public function test_bulk_worker_skips_brief_modified_after_queue_and_creates_no_draft(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => StaffRole::SuperAdmin]);
        $path = $this->workbook();
        try {
            $intake = app(EditorialWorkbook::class)->read($path, 'phase-two.xlsx');
            $intake['records'][0]['plan']['team_draft'] = 'Unverified draft outline.';
            app(EditorialPlanImport::class)->commit($admin, $intake);

            $service = app(EditorialDraftBatchService::class);
            $batch = $service->queue($admin, $intake['hash']);
            $item = EditorialDraftBatchItem::where('editorial_draft_batch_id', $batch->id)->firstOrFail();
            $brief = $item->brief;
            $plan = $brief->plan;
            $plan['team_status'] = 'Hold — manual editorial review';
            $brief->update(['plan' => $plan]);

            (new CreateEditorialPlanDraft($item->id))->handle(app(EditorialPlanImport::class), $service);
            $this->assertSame('skipped', $item->fresh()->status);
            $this->assertDatabaseCount('articles', 0);
            $this->assertSame(0, $service->inspect($intake['hash'])['counts']['ready']);
        } finally {
            unlink($path);
        }
    }

    public function test_only_administrator_can_queue_bulk_private_drafts(): void
    {
        $author = User::factory()->create();
        $this->actingAs($author)->post(route('staff.editorial-plan.draft-batches.queue'), [
            'source_hash' => str_repeat('a', 64), 'confirm_private' => '1',
        ])->assertForbidden();
        $this->get(route('staff.editorial-plan.draft-batches.preview', [
            'source_hash' => str_repeat('a', 64),
        ]))->assertForbidden();
    }

    public function test_author_cannot_read_or_import_the_admin_plan(): void
    {
        $this->actingAs(User::factory()->create())->get('/staff/editorial-plan')->assertForbidden();
        $this->post('/staff/editorial-plan/import', ['confirm_private' => 1])->assertForbidden();
    }
}
