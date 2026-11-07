<?php

use App\Services\VerifiedEditorialEvidenceIntake;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Strict guard: only still-current original prepared revision #4, with
        // no later editor changes. Published pointers remain unchanged.
        foreach ([50, 51, 52] as $articleId) {
            $updated = app(VerifiedEditorialEvidenceIntake::class)->queue($articleId);
            Log::info('AR_EDITORIAL_EVIDENCE_HANDOFF', [
                'article_id' => $articleId,
                'created_private_review_revision' => $updated,
                'human_approval' => false,
            ]);
        }
    }

    public function down(): void
    {
        // Historic editor revisions and verified research must not be deleted.
    }
};
