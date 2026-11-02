<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleSeoBatchItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ArticleSeoBatch::class, 'article_seo_batch_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function sourceRevision(): BelongsTo
    {
        return $this->belongsTo(ArticleRevision::class, 'source_revision_id');
    }
}
