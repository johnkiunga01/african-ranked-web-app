<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AuditEvent;
use App\Services\GrowthOverview;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = Article::query()->when(! $request->user()->isSuperAdmin(), fn ($query) => $query->where('user_id', $request->user()->id));

        return view('staff.dashboard', [
            'growth' => $request->user()->isSuperAdmin() ? app(GrowthOverview::class)->data() : null,
            'total' => (clone $query)->count(), 'published' => (clone $query)->whereNotNull('published_revision_id')->count(),
            'inReview' => (clone $query)->whereHas('currentRevision', fn ($q) => $q->where('status', 'in_review'))->count(),
            'events' => $request->user()->isSuperAdmin() ? AuditEvent::latest('id')->limit(12)->get() : collect(),
        ]);
    }
}
