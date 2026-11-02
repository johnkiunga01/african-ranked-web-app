<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_reports', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->string('property');
            $table->date('period_start');
            $table->date('period_end');
            $table->json('rows');
            $table->unsignedBigInteger('clicks');
            $table->unsignedBigInteger('impressions');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('integration_checks', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();
            $table->string('status');
            $table->unsignedInteger('tool_count')->default(0);
            $table->timestamp('checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_checks');
        Schema::dropIfExists('search_reports');
    }
};
