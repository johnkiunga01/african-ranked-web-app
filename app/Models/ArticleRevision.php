<?php

namespace App\Models;

use App\Enums\RevisionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleRevision extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['editorial_evidence' => 'array', 'status' => RevisionStatus::class, 'body' => 'array', 'faqs' => 'array', 'regions' => 'array', 'review_checklist' => 'array', 'reviewed_at' => 'datetime', 'published_at' => 'datetime'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function publicRecord(Article $article): array
    {
        $mediaCredit = null;
        $mediaSource = null;
        if (preg_match('~/editorial-media/([a-f0-9-]{36})\\.webp$~', (string) $this->image, $match)) {
            $asset = ArticleMediaAsset::find($match[1]);
            $mediaCredit = $asset?->credit;
        } elseif (str_contains((string) $this->image, '/editorial-art/')) {
            $mediaCredit = 'African Ranked original illustration';
        }

        return [
            'slug' => $article->slug, 'title' => $this->title, 'excerpt' => $this->excerpt,
            'category' => $this->category_slug, 'author' => $this->author_name, 'authorTitle' => $this->author_title,
            'datePublished' => $article->first_published_at->toIso8601String(), 'dateModified' => $this->published_at->toIso8601String(),
            'readTime' => $this->read_time, 'image' => $this->image, 'body' => $this->body,
            'imageCredit' => $mediaCredit, 'imageSource' => $mediaSource,
            'seoTitle' => $this->seo_title, 'metaDescription' => $this->meta_description, 'imageAlt' => $this->image_alt,
            'faqs' => $this->faqs ?? [], 'regions' => $this->regions,
            'editorialEvidence' => $this->editorial_evidence,
            'reviewer' => $this->reviewed_by ? User::find($this->reviewed_by)?->name : null,
        ];
    }
}
