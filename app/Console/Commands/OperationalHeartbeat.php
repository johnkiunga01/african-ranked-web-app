<?php

namespace App\Console\Commands;

use App\Jobs\WorkerHeartbeat;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class OperationalHeartbeat extends Command
{
    protected $signature = 'ops:heartbeat {--status : Report state without scheduling a probe} {--strict : Exit nonzero if recent heartbeats or database checks fail}';
    protected $description = 'Monitor the scheduler/worker via shared cache and durable database queue. No customer data is modified.';

    public function handle(): int
    {
        if (! $this->option('status')) {
            Cache::put('african-ranked:ops:scheduler-heartbeat', now()->toIso8601String(), now()->addHour());
            WorkerHeartbeat::dispatch();
            $this->line('OPS_HEARTBEAT_DISPATCHED '.json_encode(['scheduled_at' => now()->toIso8601String()]));
            return self::SUCCESS;
        }
        $last = function (string $key): array {
            $raw = Cache::get('african-ranked:ops:'.$key.'-heartbeat');
            $age = null;
            try {
                if ($raw) $age = (int) CarbonImmutable::parse($raw)->diffInSeconds(now(), true);
            } catch (Throwable) {
                $age = null;
            }
            return ['age_seconds' => $age, 'recent' => $age !== null && $age <= 900];
        };
        $errors = [];
        try {
            DB::connection()->getPdo();
            $jobs = Schema::hasTable('jobs') ? DB::table('jobs')->count() : null;
            $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null;
        } catch (Throwable) {
            $jobs = null;
            $failed = null;
            $errors[] = 'database_unavailable';
        }
        try {
            $scheduler = $last('scheduler');
            $worker = $last('worker');
        } catch (Throwable) {
            $scheduler = ['age_seconds' => null, 'recent' => false];
            $worker = ['age_seconds' => null, 'recent' => false];
            $errors[] = 'shared_cache_unavailable';
        }
        $report = [
            'scheduler' => $scheduler, 'worker' => $worker,
            'queue_pending' => $jobs, 'queue_failed' => $failed,
            'queue_connection' => config('queue.default'),
            'cache_driver' => config('cache.default'),
            'errors' => $errors,
        ];
        $message = 'AR_OPS_STATUS '.json_encode($report, JSON_UNESCAPED_SLASHES);
        $this->line($message);
        Log::info($message);
        $pass = $scheduler['recent'] && $worker['recent'] && $jobs !== null && $failed !== null && $errors === [];
        return $this->option('strict') && ! $pass ? self::FAILURE : self::SUCCESS;
    }
}
