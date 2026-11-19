<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_draft_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('session_version');
            $table->string('source_hash', 64)->index();
            $table->unsignedInteger('total');
            $table->timestamps();
        });

        Schema::create('editorial_draft_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('editorial_draft_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('editorial_brief_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('queued')->index();
            $table->string('message', 300)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->unique(['editorial_draft_batch_id', 'editorial_brief_id'], 'editorial_draft_batch_item_once');
            $table->index(['editorial_brief_id', 'status'], 'editorial_draft_brief_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_draft_batch_items');
        Schema::dropIfExists('editorial_draft_batches');
    }
};
