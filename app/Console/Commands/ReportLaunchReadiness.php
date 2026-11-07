<?php

namespace App\Console\Commands;

use App\Services\LaunchReadiness;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReportLaunchReadiness extends Command
{
    protected $signature = 'launch:report {--strict : Return a failing status if a release gate remains blocked}';

    protected $description = 'Report launch prerequisites without modifying site data, indexing, security or configuration.';

    public function handle(LaunchReadiness $readiness): int
    {
        $report = $readiness->report();
        $blocked = collect($report['checks'])->filter(fn ($check) => ! $check['passed'])
            ->map(fn ($check) => $check['name'])->values()->all();
        $receipt = [
            'passed' => $report['passed'],
            'total' => $report['total'],
            'blocked' => $blocked,
            'manual_gates' => count($report['manual_gates']),
            'release_approved' => false,
        ];
        $message = 'AR_LAUNCH_READINESS '.json_encode($receipt, JSON_UNESCAPED_SLASHES);
        $this->line($message);
        Log::info($message);

        return $this->option('strict') && $blocked !== [] ? self::FAILURE : self::SUCCESS;
    }
}
