<?php

use Illuminate\Support\Facades\Schedule;

// Database-backed scheduler/worker liveness check; safe and idempotent.
Schedule::command('ops:heartbeat')->everyFiveMinutes()->withoutOverlapping();

Schedule::command('content:sync')->hourly()->withoutOverlapping();

Schedule::command('content:optimize-onsite')->daily()->withoutOverlapping();
