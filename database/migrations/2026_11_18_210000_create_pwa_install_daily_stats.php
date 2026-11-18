<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pwa_install_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('tracked_on');
            $table->string('platform', 12);
            $table->string('event_type', 32);
            $table->unsignedBigInteger('total')->default(0);
            $table->timestamps();
            $table->unique(['tracked_on', 'platform', 'event_type'], 'pwa_install_day_platform_event_unique');
            $table->index(['tracked_on', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pwa_install_daily_stats');
    }
};
