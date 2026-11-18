<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Read-only reports from Google Analytics 4 and Search Console, fetched server-side
 * with a service account. Results are cached; failures return a sanitized error
 * and never expose credentials.
 */
class GoogleReports
{
    private const SCOPES = 'https://www.googleapis.com/auth/analytics.readonly https://www.googleapis.com/auth/webmasters.readonly';

    public function analyticsConfigured(): bool
    {
        return $this->credentials() !== null && preg_match('/^\d{5,15}$/', (string) config('services.google_reports.ga4_property_id')) === 1;
    }

    public function searchConsoleConfigured(): bool
    {
        return $this->credentials() !== null && filled(config('services.google_reports.search_console_site'));
    }

    public function serviceAccountEmail(): ?string
    {
        return $this->credentials()['client_email'] ?? null;
    }

    /** @return array{ok: bool, error?: string, data?: array} */
    public function analytics(bool $fresh = false): array
    {
        if (! $this->analyticsConfigured()) {
            return ['ok' => false, 'error' => 'not_configured'];
        }

        return $this->cached('ga4', $fresh, function () {
            $property = 'properties/'.config('services.google_reports.ga4_property_id');
            $ranges = $this->ranges(1);
            $metrics = ['activeUsers', 'sessions', 'screenPageViews', 'engagementRate', 'averageSessionDuration'];
            $totals = $this->ga4($property, ['dateRanges' => $ranges, 'metrics' => $this->names($metrics)]);
            $current = $this->ga4Values($totals['rows'][0] ?? null, $metrics);
            $previous = $this->ga4Values($totals['rows'][1] ?? null, $metrics);
            $one = [$ranges[0]];

            return [
                'period' => $ranges[0],
                'totals' => $current,
                'previous' => $previous,
                'daily' => $this->ga4Table($property, $one, ['date'], ['activeUsers', 'screenPageViews'], 'date', false, 60),
                'pages' => $this->ga4Table($property, $one, ['pagePath'], ['screenPageViews', 'activeUsers', 'averageSessionDuration'], 'screenPageViews', true, 15),
                'channels' => $this->ga4Table($property, $one, ['sessionDefaultChannelGroup'], ['sessions', 'engagementRate'], 'sessions', true, 10),
                'sources' => $this->ga4Table($property, $one, ['sessionSourceMedium'], ['sessions'], 'sessions', true, 10),
                'countries' => $this->ga4Table($property, $one, ['country'], ['activeUsers'], 'activeUsers', true, 10),
                'devices' => $this->ga4Table($property, $one, ['deviceCategory'], ['activeUsers'], 'activeUsers', true, 5),
                'fetched_at' => now()->toIso8601String(),
            ];
        });
    }

    /** @return array{ok: bool, error?: string, data?: array} */
    public function searchConsole(bool $fresh = false): array
    {
        if (! $this->searchConsoleConfigured()) {
            return ['ok' => false, 'error' => 'not_configured'];
        }

        return $this->cached('gsc', $fresh, function () {
            // Search Console data lags by about two days.
            $ranges = $this->ranges(3);
            $current = $this->gscTotals($ranges[0]);
            $previous = $this->gscTotals($ranges[1]);

            return [
                'period' => $ranges[0],
                'totals' => $current,
                'previous' => $previous,
                'daily' => $this->gsc($ranges[0], ['date'], 60),
                'queries' => $this->gsc($ranges[0], ['query'], 25),
                'pages' => $this->gsc($ranges[0], ['page'], 15),
                'countries' => $this->gsc($ranges[0], ['country'], 10),
                'fetched_at' => now()->toIso8601String(),
            ];
        });
    }

    public function forget(): void
    {
        Cache::forget('google_reports.ga4');
        Cache::forget('google_reports.gsc');
    }

    private function cached(string $key, bool $fresh, callable $fetch): array
    {
        $cacheKey = 'google_reports.'.$key;
        if (! $fresh && ($hit = Cache::get($cacheKey))) {
            return $hit;
        }
        try {
            $result = ['ok' => true, 'data' => $fetch()];
            Cache::put($cacheKey, $result, now()->addMinutes(max(5, config('services.google_reports.cache_minutes', 60))));

            return $result;
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'error' => $e instanceof RuntimeException ? $e->getMessage() : 'Google did not return a report. Try again shortly.'];
        }
    }

    /** Two consecutive 28-day windows ending $lagDays ago: [current, previous]. */
    private function ranges(int $lagDays): array
    {
        $end = Carbon::today()->subDays($lagDays);
        $start = $end->copy()->subDays(27);

        return [
            ['startDate' => $start->toDateString(), 'endDate' => $end->toDateString()],
            ['startDate' => $start->copy()->subDays(28)->toDateString(), 'endDate' => $start->copy()->subDay()->toDateString()],
        ];
    }

    private function names(array $names): array
    {
        return array_map(fn ($n) => ['name' => $n], $names);
    }

    private function ga4(string $property, array $body): array
    {
        $response = Http::withToken($this->token())->acceptJson()->connectTimeout(5)->timeout(20)
            ->post('https://analyticsdata.googleapis.com/v1beta/'.$property.':runReport', $body);
        $this->guard($response, 'Google Analytics');

        return $response->json() ?? [];
    }

    private function ga4Values(?array $row, array $metrics): array
    {
        $out = [];
        foreach ($metrics as $i => $name) {
            $out[$name] = (float) ($row['metricValues'][$i]['value'] ?? 0);
        }

        return $out;
    }

    private function ga4Table(string $property, array $ranges, array $dims, array $metrics, string $orderBy, bool $desc, int $limit): array
    {
        $order = in_array($orderBy, $dims, true) ? ['dimension' => ['dimensionName' => $orderBy]] : ['metric' => ['metricName' => $orderBy]];
        $json = $this->ga4($property, [
            'dateRanges' => $ranges, 'dimensions' => $this->names($dims), 'metrics' => $this->names($metrics),
            'orderBys' => [$order + ['desc' => $desc]], 'limit' => $limit,
        ]);

        return array_map(function ($row) use ($dims, $metrics) {
            $item = [];
            foreach ($dims as $i => $d) {
                $item[$d] = (string) ($row['dimensionValues'][$i]['value'] ?? '');
            }
            foreach ($metrics as $i => $m) {
                $item[$m] = (float) ($row['metricValues'][$i]['value'] ?? 0);
            }

            return $item;
        }, $json['rows'] ?? []);
    }

    private function gscTotals(array $range): array
    {
        $rows = $this->gsc($range, [], 1);

        return $rows[0] ?? ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0];
    }

    private function gsc(array $range, array $dims, int $limit): array
    {
        $site = rawurlencode((string) config('services.google_reports.search_console_site'));
        $body = ['startDate' => $range['startDate'], 'endDate' => $range['endDate'], 'rowLimit' => $limit, 'dataState' => 'final'];
        if ($dims) {
            $body['dimensions'] = $dims;
        }
        $response = Http::withToken($this->token())->acceptJson()->connectTimeout(5)->timeout(20)
            ->post('https://www.googleapis.com/webmasters/v3/sites/'.$site.'/searchAnalytics/query', $body);
        $this->guard($response, 'Search Console');
        $rows = array_map(fn ($r) => [
            'key' => $r['keys'][0] ?? null,
            'clicks' => (float) ($r['clicks'] ?? 0), 'impressions' => (float) ($r['impressions'] ?? 0),
            'ctr' => (float) ($r['ctr'] ?? 0), 'position' => (float) ($r['position'] ?? 0),
        ], $response->json('rows') ?? []);
        if ($dims === ['date']) {
            usort($rows, fn ($a, $b) => strcmp((string) $a['key'], (string) $b['key']));
        }

        return $rows;
    }

    private function guard($response, string $service): void
    {
        if ($response->successful()) {
            return;
        }
        throw new RuntimeException(match ($response->status()) {
            401 => $service.' rejected the service account sign-in. Check the credentials.',
            403 => $service.' denied access. Add '.($this->serviceAccountEmail() ?? 'the service account').' as a user on the property and enable the API in Google Cloud.',
            404 => $service.' could not find the property. Check the property ID or site URL.',
            429 => $service.' quota was reached. Reports will refresh later.',
            default => $service.' returned an error ('.$response->status().'). Try again shortly.',
        });
    }

    private function credentials(): ?array
    {
        $raw = trim((string) config('services.google_reports.credentials'));
        if ($raw === '') {
            return null;
        }
        if (! str_starts_with($raw, '{')) {
            $raw = (string) base64_decode($raw, true);
        }
        $json = json_decode($raw, true);

        return is_array($json) && filled($json['client_email'] ?? null) && filled($json['private_key'] ?? null) ? $json : null;
    }

    private function token(): string
    {
        return Cache::remember('google_reports.token', now()->addMinutes(50), function () {
            $creds = $this->credentials() ?? throw new RuntimeException('Google credentials are not configured.');
            $now = time();
            $enc = fn ($v) => rtrim(strtr(base64_encode(is_string($v) ? $v : json_encode($v)), '+/', '-_'), '=');
            $unsigned = $enc(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$enc([
                'iss' => $creds['client_email'], 'scope' => self::SCOPES,
                'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600,
            ]);
            $key = openssl_pkey_get_private($creds['private_key']);
            if ($key === false || ! openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('The Google service account key could not be read.');
            }
            $response = Http::asForm()->connectTimeout(5)->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned.'.'.$enc($signature),
            ]);
            if (! $response->successful() || ! $response->json('access_token')) {
                throw new RuntimeException('Google rejected the service account sign-in. Check the credentials.');
            }

            return $response->json('access_token');
        });
    }
}
