<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\EditorialCoverArt;
use Illuminate\Http\Response;

class EditorialCoverArtController extends Controller
{
    public function show(string $slug, string $variant, EditorialCoverArt $art): Response
    {
        abort_unless(preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug), 404);
        abort_unless(preg_match('/^(?:cover|detail-[1-9][0-9]?)$/D', $variant), 404);
        $article = Article::whereNull('trashed_at')->where('slug', $slug)
            ->with(['publishedRevision', 'currentRevision'])->firstOrFail();
        $revision = $article->publishedRevision ?? $article->currentRevision;
        abort_unless($revision, 404);

        return response($art->render($slug, $variant, $revision->title, $revision->category_slug), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }
}
