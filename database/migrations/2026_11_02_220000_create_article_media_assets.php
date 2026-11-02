<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('article_media_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
            $table->char('sha256', 64)->unique();
            $table->string('storage_path', 160)->unique();
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('bytes');
            $table->timestamps();
            $table->index(['article_id','created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('article_media_assets');
    }
};
