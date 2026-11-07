<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ArticleRecommendation extends Model
{
    protected $table = 'article_ai_requests';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('recommendation', fn (Builder $q) => $q->where('purpose', 'editorial_recommendation'));
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sourceRevision()
    {
        return $this->belongsTo(ArticleRevision::class, 'source_revision_id');
    }

    public function resultRevision()
    {
        return $this->belongsTo(ArticleRevision::class, 'result_revision_id');
    }
}
