<?php

namespace App\Console\Commands;

use App\Models\Article;
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

        // Read-only handoff receipt: no article text or personal data is emitted.
        $articles = Article::query()->whereIn('id', [50, 51, 52])->with('currentRevision')->get()->keyBy('id');
        $states = collect([50, 51, 52])->map(function (int $id) use ($articles) {
            $revision = $articles->get($id)?->currentRevision;

            $evidence = $revision?->editorial_evidence ?? [];

            return [
                'article_id' => $id,
                'status' => $revision?->status?->value ?? 'missing',
                'revision' => $revision?->number,
                'body_words' => $revision ? preg_match_all('/[\pL\pN]+/u', (string) $revision->body_markdown) : 0,
                'answer_words' => preg_match_all('/[\pL\pN]+(?:[’\x{0027}-][\pL\pN]+)*/u', (string) ($evidence['answer'] ?? '')),
                'recorded_sources' => count($evidence['sources'] ?? []),
                'recorded_facts' => count($evidence['facts'] ?? []),
                'last_verified_at' => $evidence['last_verified_at'] ?? null,
            ];
        })->all();
        $this->line('AR_EDITORIAL_HANDOFF_STATUS '.json_encode($states, JSON_UNESCAPED_SLASHES));

        return $this->option('strict') && $blocked !== [] ? self::FAILURE : self::SUCCESS;
    }
}
