<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Services\PublishingGate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

class Article extends Model
{
    protected $fillable = [
        'type', 'status', 'slug', 'edition', 'canonical_article_id', 'category_id', 'author_id', 'reviewer_id',
        'title', 'dek', 'answer_block', 'body', 'key_facts', 'faq', 'methodology_note', 'word_count',
        'frontend_payload', 'legacy_public', 'indexable', 'published_at', 'last_verified_at', 'next_verification_at',
        'sponsor_name', 'disclosure', 'ai_generated', 'ai_meta',
    ];

    protected $casts = [
        'frontend_payload' => 'array',
        'legacy_public' => 'boolean',
        'type' => ArticleType::class,
        'status' => ArticleStatus::class,
        'key_facts' => 'array',
        'faq' => 'array',
        'ai_meta' => 'array',
        'indexable' => 'boolean',
        'ai_generated' => 'boolean',
        'published_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'next_verification_at' => 'datetime',
    ];

    private bool $indexingAudited = false;

    protected static function booted(): void
    {
        static::saving(function (self $article): void {
            if ($article->status === ArticleStatus::Published) {
                $article->word_count = self::countWords($article->body);
                $failures = app(PublishingGate::class)->evaluate($article);
                if ($failures) {
                    throw new RuntimeException(implode("\n", $failures));
                }
            }
            if ($article->isDirty('indexable') && $article->indexable && ! $article->indexingAudited) {
                throw new RuntimeException('Use markIndexable() after a human audit.');
            }
        });
    }

    public function scopePubliclyVisible($query)
    {
        return $query->where(fn ($q) => $q->published()
            ->orWhere('legacy_public', true)
            ->orWhere(fn ($q) => $q->where('status', ArticleStatus::NeedsUpdate->value)
                ->whereNotNull('published_at')->where('published_at', '<=', now())));
    }

    // ---- relations ----
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(Author::class, 'reviewer_id');
    }

    public function canonical()
    {
        return $this->belongsTo(self::class, 'canonical_article_id');
    }

    public function sources()
    {
        return $this->belongsToMany(Source::class)->withPivot('supports');
    }

    public function entities()
    {
        return $this->belongsToMany(Entity::class)->withPivot('role');
    }

    public function items()
    {
        return $this->hasMany(RankingItem::class)->orderBy('position');
    }

    public function corrections()
    {
        return $this->hasMany(Correction::class)->latest('corrected_at');
    }

    // ---- scopes ----
    public function scopePublished($q)
    {
        return $q->where('status', ArticleStatus::Published->value)->where('published_at', '<=', now());
    }

    public function scopeIndexable($q)
    {
        return $q->published()->where('indexable', true);
    }

    // ---- helpers ----
    public function url(): string
    {
        $prefix = $this->edition === 'global' ? '' : '/'.$this->edition;

        return rtrim(config('africaranked.brand.url'), '/').$prefix.'/articles/'.$this->slug;
    }

    /** Canonical URL: regional copies with identical text point to the global article. */
    public function canonicalUrl(): string
    {
        return $this->canonical?->url() ?? $this->url();
    }

    /** Count words of unique body text (markdown syntax and links stripped). */
    public static function countWords(?string $markdown): int
    {
        if (! $markdown) {
            return 0;
        }

        $text = preg_replace('/```.*?```/s', ' ', $markdown);
        $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/', ' ', $text);
        $text = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $text);
        $text = strip_tags(Str::markdown($text));

        return str_word_count($text, 0, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåæçèéêëìíîïñòóôõöøùúûüýÿ');
    }

    /**
     * Run every gate; throws unless all pass. This is the ONLY way an article reaches "published".
     */
    public function publish(?PublishingGate $gate = null): self
    {
        $this->word_count = self::countWords($this->body);
        $failures = ($gate ?? app(PublishingGate::class))->evaluate($this);

        if ($failures) {
            throw new RuntimeException("Cannot publish: \n- ".implode("\n- ", $failures));
        }

        $days = config('africaranked.verification_days.'.$this->type->value, 365);

        $this->forceFill([
            'status' => ArticleStatus::Published,
            'published_at' => $this->published_at ?? now(),
            'last_verified_at' => $this->last_verified_at ?? now(),
            'next_verification_at' => ($this->last_verified_at ?? now())->copy()->addDays($days),
        ])->save();

        return $this;
    }

    /** Separate step from publish: allow search engines in only after audit. */
    public function markIndexable(): self
    {
        if ($this->status !== ArticleStatus::Published || ! $this->published_at || $this->published_at->isFuture()) {
            throw new RuntimeException('Only published articles can be made indexable.');
        }

        $this->indexingAudited = true;
        try {
            $this->forceFill(['indexable' => true])->save();
        } finally {
            $this->indexingAudited = false;
        }

        return $this;
    }
}
