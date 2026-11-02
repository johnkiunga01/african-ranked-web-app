<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->timestamp('trashed_at')->nullable()->index();
        });
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->unsignedInteger('version')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name', 120);
            $table->string('country_code', 2)->default('');
            $table->string('locale', 16)->unique();
            $table->string('currency', 3);
            $table->string('currency_symbol', 12);
            $table->string('flag', 16)->default('');
            $table->string('tagline', 500);
            $table->json('localized_terms');
            $table->json('popular_categories');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(10);
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        foreach (json_decode(file_get_contents(base_path('content/regions.json')), true) as $i => $region) {
            $terms = $region['localizedTerms'];
            $terms['newsletterSubtitle'] = 'Get new Africa-focused guides and rankings in your inbox.';
            DB::table('regions')->insert([
                'code' => $region['code'], 'name' => $region['name'], 'country_code' => $region['countryCode'],
                'locale' => $region['locale'], 'currency' => $region['currency'], 'currency_symbol' => $region['currencySymbol'],
                'flag' => $region['flag'], 'tagline' => $region['tagline'], 'localized_terms' => json_encode($terms),
                'popular_categories' => json_encode($region['popularCategories']), 'sort_order' => $region['code'] === 'global' ? 0 : 10,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        Schema::create('site_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 180)->unique();
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->unsignedBigInteger('published_revision_id')->nullable()->index();
            $table->timestamp('first_published_at')->nullable();
            $table->timestamp('trashed_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('site_page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_page_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('title', 180);
            $table->string('description', 500)->default('');
            $table->text('body_markdown');
            $table->json('body');
            $table->boolean('in_footer')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['site_page_id', 'number']);
        });
        Schema::table('site_pages', function (Blueprint $table) {
            $table->foreign('current_revision_id')->references('id')->on('site_page_revisions')->nullOnDelete();
            $table->foreign('published_revision_id')->references('id')->on('site_page_revisions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('site_pages', fn (Blueprint $table) => $table->dropForeign(['current_revision_id']));
        Schema::table('site_pages', fn (Blueprint $table) => $table->dropForeign(['published_revision_id']));
        Schema::dropIfExists('site_page_revisions');
        Schema::dropIfExists('site_pages');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('site_settings');
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['trashed_at']);
            $table->dropColumn('trashed_at');
        });
    }
};
