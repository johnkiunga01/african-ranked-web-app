<?php

namespace App\Console\Commands;

use App\Services\LaunchReadiness;
use Illuminate\Console\Command;

class CheckLaunchReadiness extends Command
{
    protected $signature = 'launch:check {--json}';

    protected $description = 'Inspect automated release prerequisites without changing configuration, publishing content or exposing secrets.';

    public function handle(LaunchReadiness $readiness): int
    {
        $report = $readiness->report();
        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Check', 'Result', 'Evidence / action'], array_map(fn ($c) => [$c['name'], $c['passed'] ? 'PASS' : 'BLOCKED', $c['detail']], $report['checks']));
            $this->line('Automated prerequisites: '.$report['passed'].' / '.$report['total'].'. This command does not authorize launch.');
            $this->line('Manual evidence still required: '.implode('; ', $report['manual_gates']).'.');
        }

        return $report['passed'] === $report['total'] ? self::SUCCESS : self::FAILURE;
    }
}
