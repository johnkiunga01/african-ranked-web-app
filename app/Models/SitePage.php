<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SitePage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['first_published_at' => 'datetime', 'trashed_at' => 'datetime'];
    }

    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(SitePageRevision::class, 'current_revision_id');
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(SitePageRevision::class, 'published_revision_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SitePageRevision::class)->orderByDesc('number');
    }

    public function publicPath(): string
    {
        return $this->slug === 'editorial-approach' ? '/strategy' : '/pages/'.$this->slug;
    }
}
