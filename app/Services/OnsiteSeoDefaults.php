<?php

namespace App\Services;

use App\Models\ArticleRevision;

class OnsiteSeoDefaults
{
    public function apply(ArticleRevision $revision): void
    {
        if (! filled($revision->seo_title)) {
            $revision->seo_title = mb_substr($revision->title.' | African Ranked', 0, 180);
        }
        if (! filled($revision->meta_description)) {
            $revision->meta_description = mb_substr($revision->excerpt ?? '', 0, 500);
        }
        // Image descriptions, source evidence and keyword demand must never be invented.
    }
}
