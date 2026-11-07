<?php

namespace Tests\Feature;

use App\Jobs\WorkerHeartbeat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OperationalReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_dispatches_a_worker_probe_without_any_editorial_change(): void
    {
        config(['cache.default' => 'array', 'queue.default' => 'database']);
        Queue::fake();
        $this->artisan('ops:heartbeat')->assertExitCode(0);
        Queue::assertPushed(WorkerHeartbeat::class, 1);
        $this->assertNotNull(Cache::get('african-ranked:ops:scheduler-heartbeat'));
    }

    public function test_worker_probe_records_completion_in_shared_cache(): void
    {
        config(['cache.default' => 'array', 'queue.default' => 'database']);
        $this->artisan('ops:heartbeat', ['--status' => true, '--strict' => true])->assertExitCode(1);
        Cache::put('african-ranked:ops:scheduler-heartbeat', now()->toIso8601String(), now()->addHour());
        (new WorkerHeartbeat)->handle();
        $this->artisan('ops:heartbeat', ['--status' => true, '--strict' => true])->assertExitCode(0);
    }

    public function test_smtp_test_refuses_to_send_without_a_verified_sender(): void
    {
        config(['mail.from.address' => 'unverified@example.test', 'mail.mailers.smtp.host' => 'mail-eu.smtp2go.com',
            'mail.mailers.smtp.username' => 'test-user', 'mail.mailers.smtp.password' => 'test-secret']);
        $this->artisan('ops:mail-smoke', ['--to' => 'john@salibay.com'])->assertExitCode(1);
    }
}
