<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AuditEvent;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $admin = $request->user()->isSuperAdmin();
        $articles = Article::query()->whereNull('trashed_at')
            ->when(! $admin, fn ($query) => $query->where('user_id', $request->user()->id));

        return view('staff.dashboard', [
            'total' => (clone $articles)->count(),
            'published' => (clone $articles)->whereNotNull('published_revision_id')->count(),
            'inReview' => (clone $articles)->whereHas('currentRevision', fn ($query) => $query->where('status', 'in_review'))->count(),
            'drafts' => (clone $articles)->whereNull('published_revision_id')->count(),
            'updatedDrafts' => (clone $articles)
                ->whereNotNull('published_revision_id')
                ->whereColumn('current_revision_id', '!=', 'published_revision_id')->count(),
            'recentArticles' => (clone $articles)->with(['currentRevision', 'author'])
                ->orderByDesc('updated_at')->limit(6)->get(),
            'events' => $admin ? AuditEvent::latest('id')->limit(5)->get() : collect(),
            'isAdmin' => $admin,
        ]);
    }
}
