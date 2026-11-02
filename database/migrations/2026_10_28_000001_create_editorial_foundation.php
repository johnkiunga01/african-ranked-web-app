<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('author')->index();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('session_version')->default(1);
            $table->text('bio')->nullable();
            $table->string('job_title')->default('Contributor');
        });
        Schema::create('staff_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('name');
            $table->string('role')->default('author');
            $table->string('token_hash', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->index(['email', 'accepted_at']);
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('icon');
            $table->string('color', 16);
            $table->timestamps();
        });
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('current_revision_id')->nullable();
            $table->unsignedBigInteger('published_revision_id')->nullable()->index();
            $table->timestamp('first_published_at')->nullable();
            $table->timestamps();
        });
        Schema::create('article_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('number');
            $table->string('status')->default('draft')->index();
            $table->string('title');
            $table->text('excerpt');
            $table->string('category_slug');
            $table->text('body_markdown')->nullable();
            $table->json('body');
            $table->json('faqs')->nullable();
            $table->json('regions')->nullable();
            $table->text('image')->nullable();
            $table->string('author_name');
            $table->string('author_title');
            $table->unsignedInteger('read_time')->default(1);
            $table->text('source_notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->json('review_checklist')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'number']);
        });
        Schema::table('articles', function (Blueprint $table) {
            $table->foreign('current_revision_id')->references('id')->on('article_revisions')->nullOnDelete();
            $table->foreign('published_revision_id')->references('id')->on('article_revisions')->nullOnDelete();
        });
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['current_revision_id']);
            $table->dropForeign(['published_revision_id']);
        });
        Schema::dropIfExists('article_revisions');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('staff_invitations');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'is_active', 'session_version', 'bio', 'job_title']);
        });
    }
};
