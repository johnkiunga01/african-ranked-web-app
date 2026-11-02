<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_revisions', function (Blueprint $table) {
            $table->string('seo_title', 180)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('focus_keyword', 180)->nullable();
            $table->string('image_alt', 300)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('article_revisions', fn (Blueprint $table) => $table->dropColumn(['seo_title', 'meta_description', 'focus_keyword', 'image_alt']));
    }
};
