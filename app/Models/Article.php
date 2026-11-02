<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    // Ownership, revision pointers and publication can only be changed by explicit services.
    protected $fillable = ['slug'];

    protected function casts(): array
    {
        return ['first_published_at' => 'datetime', 'trashed_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(ArticleRevision::class, 'current_revision_id');
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(ArticleRevision::class, 'published_revision_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class)->orderByDesc('number');
    }
}
