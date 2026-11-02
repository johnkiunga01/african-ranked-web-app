<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\PublicationQuality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContentReadinessController extends Controller
{
    public function __invoke(Request $request, PublicationQuality $quality)
    {
        Gate::authorize('viewAny', Article::class);
        $articles = Article::whereNull('trashed_at')->with(['currentRevision', 'author'])
            ->when(! $request->user()->isSuperAdmin(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('updated_at')->paginate(25);
        $reports = [];
        foreach ($articles as $article) {
            if ($article->currentRevision) {
                $reports[$article->id] = $quality->inspect($article->currentRevision);
            }
        }

        return view('staff.content-readiness', compact('articles', 'reports'));
    }
}
