<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * A tiny probe that confirms the worker consumed a database-queued job.
 * No customer data, emails, or network calls are involved.
 */
class WorkerHeartbeat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 15;

    public function handle(): void
    {
        Cache::put('african-ranked:ops:worker-heartbeat', now()->toIso8601String(), now()->addHour());
    }
}
