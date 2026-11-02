<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('content:sync')->hourly()->withoutOverlapping();

Schedule::command('content:optimize-onsite')->daily()->withoutOverlapping();
