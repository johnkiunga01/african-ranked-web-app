<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Production created article_media_assets from the first version of
 * 2026_11_02_220000_create_article_media_assets, before alt, caption, credit
 * and rights_confirmed_at were added to that file. Every upload has failed
 * since with "column alt does not exist". Add whichever columns are missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        $missing = array_values(array_filter(
            ['alt', 'caption', 'credit', 'rights_confirmed_at'],
            fn (string $column) => ! Schema::hasColumn('article_media_assets', $column),
        ));

        if ($missing === []) {
            return;
        }

        Schema::table('article_media_assets', function (Blueprint $table) use ($missing) {
            if (in_array('alt', $missing, true)) {
                $table->string('alt', 300)->nullable();
            }
            if (in_array('caption', $missing, true)) {
                $table->string('caption', 500)->nullable();
            }
            if (in_array('credit', $missing, true)) {
                $table->string('credit', 180)->nullable();
            }
            if (in_array('rights_confirmed_at', $missing, true)) {
                // Nullable so existing rows (uploaded before the column existed) stay valid.
                $table->timestamp('rights_confirmed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty: on fresh databases these columns belong to the create migration.
    }
};
