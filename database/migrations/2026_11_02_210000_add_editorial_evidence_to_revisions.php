<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_revisions', fn (Blueprint $table) => $table->json('editorial_evidence')->nullable());
        Schema::table('editorial_briefs', fn (Blueprint $table) => $table->foreignId('article_id')->nullable()->unique()->constrained()->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('article_revisions', fn (Blueprint $table) => $table->dropColumn('editorial_evidence'));
        Schema::table('editorial_briefs', function (Blueprint $table) {
            $table->dropForeign(['article_id']);
            $table->dropColumn('article_id');
        });
    }
};
