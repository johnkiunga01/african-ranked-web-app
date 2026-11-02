<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('bio')->nullable();
            $t->string('credentials')->nullable();
            $t->string('profile_url')->nullable();
            $t->boolean('is_reviewer')->default(false);
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->timestamps();
        });

        // Canonical fact store: each real-world thing lives here once.
        Schema::create('entities', function (Blueprint $t) {
            $t->id();
            $t->string('type')->index();            // country, region, city, park, company, dish, artist...
            $t->string('slug')->unique();
            $t->string('name');
            $t->char('country_code', 2)->nullable()->index();
            $t->foreignId('parent_id')->nullable()->constrained('entities')->nullOnDelete();
            $t->json('attributes')->nullable();     // structured facts with their own source/date
            $t->json('same_as')->nullable();        // Wikidata, official site, etc.
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
        });

        Schema::create('sources', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('publisher')->nullable();
            $t->string('url', 1000)->unique();
            $t->date('published_at')->nullable();
            $t->date('accessed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('articles', function (Blueprint $t) {
            $t->id();
            $t->string('type')->index();                       // ranking|guide|sponsored|affiliate
            $t->string('status')->default('draft')->index();
            $t->string('slug');
            $t->string('edition', 8)->default('global');
            $t->foreignId('canonical_article_id')->nullable()->constrained('articles')->nullOnDelete();
            $t->foreignId('category_id')->constrained();
            $t->foreignId('author_id')->nullable()->constrained('authors')->nullOnDelete();
            $t->foreignId('reviewer_id')->nullable()->constrained('authors')->nullOnDelete();

            $t->string('title');
            $t->string('dek', 320)->nullable();                // meta description / standfirst
            $t->text('answer_block')->nullable();              // 40-60 word direct answer
            $t->longText('body')->nullable();                  // markdown, excludes FAQ
            $t->json('key_facts')->nullable();                 // [{label,value,unit?,verified_on}]
            $t->json('faq')->nullable();                       // [{question,answer}]
            $t->text('methodology_note')->nullable();

            $t->unsignedInteger('word_count')->default(0);
            $t->boolean('indexable')->default(false)->index(); // staged rollout: noindex until audited
            $t->timestamp('published_at')->nullable()->index();
            $t->timestamp('last_verified_at')->nullable();
            $t->timestamp('next_verification_at')->nullable()->index();

            // Monetization (built now, used later)
            $t->string('sponsor_name')->nullable();
            $t->text('disclosure')->nullable();

            // Agent provenance
            $t->boolean('ai_generated')->default(false);
            $t->json('ai_meta')->nullable();                   // model, needs_verification[], claims[]

            // Legacy UI payload preserves existing text, dates and rendering exactly.
            $t->json('frontend_payload')->nullable();
            $t->boolean('legacy_public')->default(false);
            $t->timestamps();
            $t->unique(['slug', 'edition']);
        });

        Schema::create('article_source', function (Blueprint $t) {
            $t->id();
            $t->foreignId('article_id')->constrained()->cascadeOnDelete();
            $t->foreignId('source_id')->constrained()->cascadeOnDelete();
            $t->string('supports')->nullable();                // which claim/section this source backs
            $t->unique(['article_id', 'source_id']);
        });

        Schema::create('article_entity', function (Blueprint $t) {
            $t->id();
            $t->foreignId('article_id')->constrained()->cascadeOnDelete();
            $t->foreignId('entity_id')->constrained()->cascadeOnDelete();
            $t->string('role')->default('mentioned');          // about|mentioned
            $t->unique(['article_id', 'entity_id']);
        });

        Schema::create('ranking_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('article_id')->constrained()->cascadeOnDelete();
            $t->foreignId('entity_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('position');
            $t->decimal('score_total', 4, 1)->nullable();
            $t->json('scores')->nullable();                    // {criterion: score}
            $t->text('blurb')->nullable();
            $t->unique(['article_id', 'position']);
        });

        Schema::create('corrections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('article_id')->constrained()->cascadeOnDelete();
            $t->text('summary');
            $t->timestamp('corrected_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['corrections', 'ranking_items', 'article_entity', 'article_source', 'articles',
            'sources', 'entities', 'categories', 'authors'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
