<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Http\Requests\StoreAgentDraftRequest;
use App\Models\Article;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Source;
use App\Services\PublishingGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * POST /api/agent/drafts
 * The agent can ONLY create drafts. Publishing always goes through a human
 * and Article::publish() (all gates). Entities the agent proposes are stored unverified.
 */
class AgentDraftController extends Controller
{
    public function __invoke(StoreAgentDraftRequest $request): JsonResponse
    {
        $d = $request->validated();
        $edition = $d['edition'] ?? 'global';

        if (Article::where('slug', $d['slug'])->where('edition', $edition)->exists()) {
            return response()->json(['error' => 'Slug already exists for this edition.'], 409);
        }

        $article = DB::transaction(function () use ($d, $edition) {
            $article = Article::create([
                'type' => $d['type'],
                'status' => ArticleStatus::Draft,
                'slug' => $d['slug'],
                'edition' => $edition,
                'category_id' => Category::where('slug', $d['category'])->firstOrFail()->id,
                'title' => $d['title'],
                'dek' => $d['dek'],
                'answer_block' => $d['answer_block'],
                'body' => $d['body'],
                'key_facts' => $d['key_facts'],
                'faq' => $d['faq'],
                'methodology_note' => $d['methodology_note'] ?? null,
                'word_count' => Article::countWords($d['body']),
                'indexable' => false,
                'ai_generated' => true,
                'ai_meta' => array_merge($d['agent'] ?? [], ['needs_verification' => $d['needs_verification']]),
            ]);

            foreach ($d['sources'] as $s) {
                $source = Source::firstOrCreate(
                    ['url' => $s['url']],
                    collect($s)->only(['title', 'publisher', 'published_at', 'accessed_at'])->all()
                );
                $article->sources()->syncWithoutDetaching([$source->id => ['supports' => $s['supports'] ?? null]]);
            }

            $entityIds = [];
            foreach ($d['entities'] as $e) {
                $entity = Entity::firstOrCreate(
                    ['slug' => $e['slug']],
                    ['name' => $e['name'], 'type' => $e['type'], 'country_code' => $e['country_code'] ?? null] // verified_at stays null
                );
                $entityIds[$e['slug']] = $entity->id;
                $article->entities()->syncWithoutDetaching([$entity->id => ['role' => $e['role']]]);
            }

            foreach ($d['ranking_items'] ?? [] as $i) {
                abort_unless(isset($entityIds[$i['entity_slug']]), 422, "Ranking item entity '{$i['entity_slug']}' is not listed in entities.");
                $article->items()->create([
                    'entity_id' => $entityIds[$i['entity_slug']],
                    'position' => $i['position'],
                    'scores' => $i['scores'],
                    'score_total' => $i['score_total'],
                    'blurb' => $i['blurb'],
                ]);
            }

            return $article;
        });

        return response()->json([
            'id' => $article->id,
            'status' => $article->status->value,
            'word_count' => $article->word_count,
            'gate_failures_preview' => app(PublishingGate::class)->evaluate($article->fresh()),
        ], 201);
    }
}
