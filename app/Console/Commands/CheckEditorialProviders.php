<?php

namespace App\Console\Commands;

use App\Exceptions\GeminiConnectionException;
use App\Exceptions\OpenSeoConnectionException;
use App\Services\GeminiAssistant;
use App\Services\OpenSeoMcp;
use App\Services\ProviderCredentials;
use Illuminate\Console\Command;
use Throwable;

class CheckEditorialProviders extends Command
{
    protected $signature = 'editorial:check-providers {--live : Make free OpenSEO reads and one small Gemini generation request}';

    protected $description = 'Report safe provider configuration and optionally test live access without changing articles or queueing jobs.';

    public function handle(): int
    {
        $credentials = app(ProviderCredentials::class);
        $checks = [];
        foreach (['openseo', 'gemini'] as $provider) {
            $checks[$provider] = ['configured' => filled($credentials->key($provider)),
                'credential_source' => filled(config("services.$provider.key")) ? 'environment' : 'secure_storage_or_missing'];
        }
        $checks['openseo']['project'] = config('services.openseo.project_id');
        $checks['openseo']['location'] = config('services.openseo.location_code');
        $checks['gemini']['model'] = config('services.gemini.model');
        $ok = $checks['openseo']['configured'] && $checks['gemini']['configured'] && filled($checks['openseo']['project']);
        if ($this->option('live')) {
            try {
                $client = app(OpenSeoMcp::class);
                $checks['openseo']['tool_count'] = $client->check();
                $account = $client->research('whoami', []);
                $checks['openseo']['credits_remaining'] = $account['creditsRemaining'] ?? null;
                $projects = $client->research('list_projects', []);
                $rows = $projects['projects'] ?? $projects;
                $accessible = collect($rows)->contains(fn ($row) => is_array($row) && ($row['id'] ?? null) === $checks['openseo']['project']);
                $checks['openseo']['project_access'] = $accessible;
                $checks['openseo']['live'] = $accessible ? 'passed' : 'project_not_found';
                $ok = $ok && $accessible;
            } catch (OpenSeoConnectionException $exception) {
                $checks['openseo']['live'] = 'failed';
                $checks['openseo']['message'] = $exception->getMessage();
                $ok = false;
            } catch (Throwable) {
                $checks['openseo']['live'] = 'failed';
                $checks['openseo']['message'] = 'Connection or configuration check failed. No credentials or provider response are printed.';
                $ok = false;
            }
            try {
                $result = app(GeminiAssistant::class)->generate(['task' => 'Connection check; return status ok.'],
                    ['type' => 'OBJECT', 'properties' => ['status' => ['type' => 'STRING']], 'required' => ['status']],
                    'Return only {"status":"ok"}.', 256);
                $passed = ($result['status'] ?? null) === 'ok';
                $checks['gemini']['live'] = $passed ? 'passed' : 'invalid_result';
                $ok = $ok && $passed;
            } catch (GeminiConnectionException $exception) {
                $checks['gemini']['live'] = 'failed';
                $checks['gemini']['message'] = $exception->getMessage();
                $ok = false;
            } catch (Throwable) {
                // Never print unexpected exceptions, HTTP bodies or request URLs.
                $checks['gemini']['live'] = 'failed';
                $checks['gemini']['message'] = 'Generation failed. Check model access, key restrictions, region and quota. No article was sent or changed.';
                $ok = false;
            }
        }
        $this->line(json_encode(['role' => config('app.role', 'unspecified'), 'configuration_cached' => app()->configurationIsCached(),
            'providers' => $checks, 'articles_changed' => false], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
