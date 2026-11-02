<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\SeoAutoFix;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SeoFixController extends Controller
{
    public function fix(Request $request, Article $article, SeoAutoFix $fixer)
    {
        $data = $request->validate(['revision_id' => ['required', 'integer'], 'provider' => ['required', 'in:local,gemini']]);
        try {
            $changes = $fixer->apply($request->user(), $article, (int) $data['revision_id'], $data['provider'] === 'gemini');
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['seo' => $exception->getMessage()]);
        }

        return redirect()->route('staff.articles.seo', $article)->with('status', count($changes) ? 'SEO fixes saved in a new draft: '.implode(', ', array_map(fn ($key) => str_replace('_', ' ', $key), $changes)).'. Published content stays unchanged.' : 'No additional safe automatic fixes are available for this saved revision.');
    }
}
