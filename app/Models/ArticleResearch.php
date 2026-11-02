<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleResearch extends Model
{
    protected $table = 'article_research';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['brief' => 'array', 'researched_at' => 'datetime'];
    }

    public static function current(Article $article): ?self
    {
        $research = static::where('article_id', $article->id)->where('status', 'completed')
            ->where('researched_at', '>=', now()->subDays(30))->latest('id')->first();
        $scope = $research?->brief['article_scope'] ?? null;
        if ($scope && ($scope['title'] !== $article->currentRevision->title || ! in_array($article->currentRevision->focus_keyword, [$scope['focus_keyword'] ?? null, $research->keyword], true))) {
            return null;
        }

        return $research;
    }
}
