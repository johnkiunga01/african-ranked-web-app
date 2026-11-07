<?php

use App\Services\PreparedEditorialIntake;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // This data-only migration acts only on the three owner-identified short
        // drafts. It skips revised, published, removed or unexpected CMS records.
        foreach ([50, 51, 52] as $articleId) {
            $queued = app(PreparedEditorialIntake::class)->queue($articleId);
            Log::info('AR_PREPARED_EDITORIAL_INTAKE', [
                'article_id' => $articleId,
                'queued_for_review' => $queued,
                'published' => false,
            ]);
        }
    }

    public function down(): void
    {
        // Do not roll back historical editorial work or delete new revisions.
        // The prior revisions remain available in the CMS revision history.
    }
};
