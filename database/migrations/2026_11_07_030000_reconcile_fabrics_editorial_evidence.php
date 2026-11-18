<?php

use App\Services\VerifiedEditorialEvidenceIntake;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $reconciled = app(VerifiedEditorialEvidenceIntake::class)->reconcileEditedFabrics();
        Log::info('AR_FABRICS_EVIDENCE_RECONCILIATION', [
            'article_id' => 52,
            'created_preserving_editor_changes' => $reconciled,
            'human_approval' => false,
        ]);
    }

    public function down(): void
    {
        // Never remove historical editorial revisions.
    }
};
