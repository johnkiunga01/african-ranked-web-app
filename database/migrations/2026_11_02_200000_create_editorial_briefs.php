<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_briefs', function (Blueprint $table) {
            $table->id();
            $table->string('plan_id', 40)->unique();
            $table->string('slug', 180)->unique();
            $table->unsignedTinyInteger('wave')->index();
            $table->string('category_slug', 60)->index();
            $table->string('title', 180);
            $table->string('keyword', 180);
            $table->json('plan');
            $table->string('source_hash', 64);
            $table->foreignId('imported_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_briefs');
    }
};
