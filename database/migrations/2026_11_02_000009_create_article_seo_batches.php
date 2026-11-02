<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_seo_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('session_version');
            $table->timestamps();
        });
        Schema::create('article_seo_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_seo_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_revision_id')->constrained('article_revisions')->cascadeOnDelete();
            $table->foreignId('result_revision_id')->nullable()->constrained('article_revisions')->nullOnDelete();
            $table->string('status')->default('queued')->index();
            $table->string('message', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'source_revision_id'], 'seo_batch_revision_once');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_seo_batch_items');
        Schema::dropIfExists('article_seo_batches');
    }
};
