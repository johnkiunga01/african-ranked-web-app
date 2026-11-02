<?php

namespace Tests\Feature\Staff;

use App\Enums\StaffRole;
use App\Models\Article;
use App\Models\EditorialBrief;
use App\Models\User;
use App\Services\EditorialPlanImport;
use App\Services\EditorialWorkbook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_author_cannot_read_or_import_the_admin_plan(): void
    {
        $this->actingAs(User::factory()->create())->get('/staff/editorial-plan')->assertForbidden();
        $this->post('/staff/editorial-plan/import', ['confirm_private' => 1])->assertForbidden();
    }
}
