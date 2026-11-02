<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revision_id')->constrained('article_revisions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model');
            $table->string('status')->default('running');
            $table->unsignedBigInteger('result_revision_id')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'revision_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_generations');
    }
};
