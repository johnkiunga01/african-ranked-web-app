<?php

namespace App\Services;

use App\Exceptions\OpenSeoConnectionException;
use App\Models\Article;
use App\Models\ArticleResearch;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Throwable;

class ArticleResearcher
{
    public function research(User $actor, Article $article): ArticleResearch
    {
        Gate::forUser($actor)->authorize('curate', $article);
        if ($saved = ArticleResearch::current($article)) {
            return $saved;
        }
        $project = config('services.openseo.project_id');
        if (! $project || ! app(ProviderCredentials::class)->key('openseo')) {
            throw new RuntimeException('Configure the OpenSEO project and credential in Integrations before research-led optimisation.');
        }
        $keyword = $article->currentRevision->focus_keyword ?: preg_replace('/\s*(?:for\s*)?20\d{2}$/u', '', preg_replace('/^(?:The\s+)?(?:Top\s+)?\d+\s+/iu', '', $article->currentRevision->title));
        $keyword = mb_substr(trim($keyword), 0, 80);
        $location = (int) config('services.openseo.location_code', 2840);
        $research = ArticleResearch::create(['article_id' => $article->id, 'user_id' => $actor->id, 'keyword' => $keyword, 'location_code' => $location, 'language_code' => 'en']);
        try {
            $client = app(OpenSeoMcp::class);
            $metrics = $client->research('get_keyword_metrics', ['projectId' => $project, 'keywords' => [$keyword], 'locationCode' => $location, 'languageCode' => 'en', 'includeMonthlyTrends' => true, 'includeClickstreamData' => false]);
            $serp = $client->research('get_serp_results', ['projectId' => $project, 'queries' => [['keyword' => $keyword, 'locationCode' => $location, 'languageCode' => 'en', 'device' => 'desktop']], 'depth' => 10]);
            $result = collect($serp['results'] ?? [])->first(fn ($row) => ($row['ok'] ?? false) && mb_strtolower($row['keyword'] ?? '') === mb_strtolower($keyword));
            if (! $result) {
                throw new RuntimeException;
            }
            $metric = collect($metrics['keywords'] ?? [])->first(fn ($row) => mb_strtolower($row['keyword'] ?? '') === mb_strtolower($keyword));
            $brief = $this->brief($keyword, $location, $metric, $result['items'], [], ['metrics' => $metrics['meta'] ?? [], 'serp' => $serp['meta'] ?? []]);
            $brief['article_scope'] = ['title' => $article->currentRevision->title, 'focus_keyword' => $article->currentRevision->focus_keyword];
            $research->update(['status' => 'completed', 'researched_at' => now(), 'brief' => $brief]);
            AuditEvent::record($actor, 'article.researched', $article, ['research_id' => $research->id, 'location_code' => $location]);

            return $research;
        } catch (OpenSeoConnectionException $exception) {
            $research->update(['status' => 'failed']);
            throw $exception;
        } catch (Throwable) {
            $research->update(['status' => 'failed']);
            throw new RuntimeException('OpenSEO research failed. Gemini was not called. Check provider access and credits before requesting a new revision.');
        }
    }

    public function brief(string $keyword, int $location, ?array $metric, array $serp, array $ideas = [], array $meta = []): array
    {
        $organic = collect($serp)->filter(fn ($item) => ($item['type'] ?? '') === 'organic')->values()->take(10)->all();

        return [
            'source' => 'OpenSEO MCP', 'retrieved_at' => now()->toIso8601String(),
            'keyword' => $keyword, 'market' => ['location_code' => $location, 'language' => 'en', 'device' => 'desktop', 'scope' => $location === 2840 ? 'United States benchmark, not global search volume' : 'Selected national market, not global search volume'],
            'metrics' => ['search_volume' => $metric['search_volume'] ?? null, 'keyword_difficulty' => $metric['keyword_difficulty'] ?? null, 'intent' => $metric['main_intent'] ?? null, 'cpc' => $metric['cpc'] ?? null, 'competition' => $metric['competition'] ?? null, 'monthly_searches' => array_slice($metric['monthly_searches'] ?? [], 0, 12)],
            'serp_results' => $organic, 'related_keywords' => $ideas, 'provider_metadata' => $meta,
            'limits' => ['SERP titles and snippets are research leads, not verified article facts or a full competitor-page audit.', 'Google redirect URLs do not establish the destination competitor domain.', 'Missing metrics remain unknown. CPC and advertising competition do not predict affiliate revenue or organic difficulty.', 'Do not copy competitors or invent factual gaps. Match search intent while preserving supported article claims.'],
        ];
    }
}
