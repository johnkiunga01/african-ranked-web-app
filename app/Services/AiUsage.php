<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AiUsage
{
    public function remaining(User $actor): int
    {
        $since = now()->startOfDay();
        $used = DB::table('article_generations')->where('user_id', $actor->id)->where('created_at', '>=', $since)->count()
            + DB::table('article_ai_requests')->where('user_id', $actor->id)->where('created_at', '>=', $since)->count();
        $queued = DB::table('article_seo_batch_items')->join('article_seo_batches', 'article_seo_batches.id', '=', 'article_seo_batch_items.article_seo_batch_id')
            ->where('article_seo_batches.user_id', $actor->id)->whereIn('status', ['queued', 'researching'])->count();

        return max(0, ($actor->isSuperAdmin() ? 20 : 5) - $used - $queued);
    }

    private function quota(User $actor): void
    {
        User::lockForUpdate()->findOrFail($actor->id);
        $since = now()->startOfDay();
        $used = DB::table('article_generations')->where('user_id', $actor->id)->where('created_at', '>=', $since)->count()
            + DB::table('article_ai_requests')->where('user_id', $actor->id)->where('created_at', '>=', $since)->count();
        $limit = $actor->isSuperAdmin() ? 20 : 5;
        if ($used >= $limit) {
            throw ValidationException::withMessages(['ai' => 'Your daily AI request limit has been reached ('.$limit.' attempts per UTC day). No provider call was made.']);
        }
    }

    public function generation(User $actor, Article $article, int $revision, string $provider = 'openai'): int
    {
        return DB::transaction(function () use ($actor, $article, $revision, $provider) {
            $this->quota($actor);

            return DB::table('article_generations')->insertGetId(['article_id' => $article->id, 'revision_id' => $revision, 'user_id' => $actor->id, 'model' => $provider === 'gemini' ? config('services.gemini.model') : config('services.openai.article_model'), 'status' => 'running', 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    public function request(User $actor, Article $article, int $revision, string $purpose, string $provider): int
    {
        return DB::transaction(function () use ($actor, $article, $revision, $purpose, $provider) {
            $this->quota($actor);

            return DB::table('article_ai_requests')->insertGetId(['article_id' => $article->id, 'source_revision_id' => $revision, 'user_id' => $actor->id, 'purpose' => $purpose, 'provider' => $provider, 'status' => 'running', 'created_at' => now(), 'updated_at' => now()]);
        });
    }
}
