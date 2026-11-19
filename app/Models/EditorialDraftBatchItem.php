<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorialDraftBatchItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(EditorialDraftBatch::class, 'editorial_draft_batch_id');
    }

    public function brief(): BelongsTo
    {
        return $this->belongsTo(EditorialBrief::class, 'editorial_brief_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
