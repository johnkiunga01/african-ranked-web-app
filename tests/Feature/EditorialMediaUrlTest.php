<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Support\EditorialMediaUrl;
use Tests\TestCase;

class EditorialMediaUrlTest extends TestCase
{
    public function test_only_known_first_party_media_is_resolved_to_current_origin(): void
    {
        config(['app.url' => 'https://africanranked.com']);
        $old = 'https://african-ranked-web-app-production.up.railway.app';
        $this->assertSame('https://africanranked.com/editorial-art/test/cover.svg?width=600', EditorialMediaUrl::current($old.'/editorial-art/test/cover.svg?width=600'));
        $this->assertSame('https://africanranked.com/editorial-media/example.webp', EditorialMediaUrl::current($old.'/editorial-media/example.webp'));
        foreach ([$old.'/articles/test', 'https://images.pexels.com/photos/1/photo.jpeg', $old.'.evil.test/editorial-art/test.svg', 'https://user:pass@african-ranked-web-app-production.up.railway.app/editorial-art/test.svg', null] as $url) {
            $this->assertSame($url, EditorialMediaUrl::current($url));
        }
    }

    public function test_public_media_resolution_preserves_the_saved_revision_and_external_citations(): void
    {
        config(['app.url' => 'https://africanranked.com']);
        $old = 'https://african-ranked-web-app-production.up.railway.app';
        $body = [['type' => 'image', 'url' => $old.'/editorial-art/test/detail.svg', 'alt' => 'Original illustration'], ['type' => 'link', 'url' => $old.'/editorial-art/test/source.svg', 'text' => 'Retained citation']];
        $revision = new ArticleRevision(['image' => $old.'/editorial-art/test/cover.svg', 'body' => $body, 'published_at' => now()]);
        $article = new Article(['slug' => 'test']);
        $article->first_published_at = now();
        $rendered = $revision->publicRecord($article);
        $this->assertSame('https://africanranked.com/editorial-art/test/cover.svg', $rendered['image']);
        $this->assertSame('https://africanranked.com/editorial-art/test/detail.svg', $rendered['body'][0]['url']);
        $this->assertSame($body[1]['url'], $rendered['body'][1]['url']);
        $this->assertSame($old.'/editorial-art/test/cover.svg', $revision->image);
        $this->assertSame($body, $revision->body);
    }
}
