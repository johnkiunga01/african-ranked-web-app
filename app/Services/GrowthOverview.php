<?php

namespace App\Services;

use App\Models\Article;
use App\Models\GrowthPlan;
use App\Models\SearchReport;
use App\Support\Catalog;
use Illuminate\Support\Facades\DB;

class GrowthOverview
{
    public function data(): array
    {
        $articles = Article::with(['currentRevision', 'publishedRevision'])->latest('id')->get();
        $inventory = $articles->map(function ($article) {
            $revision = $article->currentRevision;
            $issues = [];
            if (! $article->user_id) {
                $issues[] = 'Assign an author';
            }
            foreach (['excerpt' => 'Add a search description', 'image' => 'Add a hero image', 'source_notes' => 'Record research sources'] as $field => $label) {
                if (! filled($revision?->$field)) {
                    $issues[] = $label;
                }
            }
            if (! $revision?->body) {
                $issues[] = 'Write the article body';
            }
            if (! $revision?->reviewed_at) {
                $issues[] = 'Complete editorial review';
            }

            return ['article' => $article, 'title' => $revision?->title ?? $article->slug, 'issues' => $issues];
        });
        $seeds = app(Catalog::class)->data('articles');
        $cmsSlugs = $articles->pluck('slug')->all();

        return [
            'latestReport' => SearchReport::latest('id')->first(),
            'openseoConfigured' => filled(app(ProviderCredentials::class)->key('openseo')),
            'openseoCheck' => DB::table('integration_checks')->where('provider', 'openseo')->first(),
            'inventory' => $inventory,
            'cmsTotal' => $articles->count(),
            'publishedTotal' => $articles->whereNotNull('published_revision_id')->count(),
            'needsWork' => $inventory->filter(fn ($row) => count($row['issues']) > 0)->count(),
            'sampleTotal' => collect($seeds)->reject(fn ($a) => in_array($a['slug'], $cmsSlugs, true))->count(),
            'reviewTotal' => $articles->filter(fn ($a) => $a->currentRevision?->status?->value === 'in_review')->count(),
            'newsletterRequests' => DB::table('newsletter_subscribers')->count(),
            'plans' => GrowthPlan::all()->keyBy('provider'),
            'providers' => config('growth.providers'),
            'target' => config('growth.article_target'),
            'acceptedTotal' => app(LaunchReadiness::class)->acceptedArticles(),
        ];
    }
}
