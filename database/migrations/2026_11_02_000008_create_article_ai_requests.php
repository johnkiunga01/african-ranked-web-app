<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_ai_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_revision_id')->constrained('article_revisions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose');
            $table->string('provider');
            $table->string('status')->default('running');
            $table->unsignedBigInteger('result_revision_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'source_revision_id', 'purpose', 'provider'], 'article_ai_request_once');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_ai_requests');
    }
};
