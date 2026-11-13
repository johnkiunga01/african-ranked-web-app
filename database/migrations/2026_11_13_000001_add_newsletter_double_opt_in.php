<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('verification_token_hash', 64)->nullable()->index();
            $table->timestamp('verification_expires_at')->nullable();
            $table->timestamp('verification_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropIndex(['verification_token_hash']);
            $table->dropColumn([
                'verified_at', 'unsubscribed_at', 'verification_token_hash',
                'verification_expires_at', 'verification_sent_at',
            ]);
        });
    }
};
