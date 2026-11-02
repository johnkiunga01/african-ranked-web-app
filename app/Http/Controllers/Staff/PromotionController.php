<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AuditEvent;
use App\Services\AiUsage;
use App\Services\GeminiAssistant;
use App\Services\ProviderCredentials;
use App\Support\Catalog;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class PromotionController extends Controller
{
    public function show(Article $article, Catalog $catalog)
    {
        Gate::authorize('view', $article);
        abort_if($article->trashed_at, 422);
        $revision = $article->publishedRevision ?: $article->currentRevision;
        $saved = DB::table('article_ai_requests')->where('article_id', $article->id)->where('source_revision_id', $revision->id)->where('purpose', 'promotion')->where('status', 'completed')->first();
        $kit = $saved ? json_decode($saved->payload, true) : ['headline' => $revision->title, 'social_post' => $revision->title.'. '.$revision->excerpt, 'newsletter_summary' => $revision->excerpt];

        return view('staff.articles.promotion', ['article' => $article, 'revision' => $revision, 'kit' => $kit, 'url' => $catalog->url('/articles/'.$article->slug), 'aiGenerated' => (bool) $saved]);
    }

    public function generate(Request $request, Article $article, GeminiAssistant $assistant, AiUsage $usage)
    {
        Gate::authorize('curate', $article);
        $data = $request->validate(['revision_id' => ['required', 'integer']]);
        $revision = $article->publishedRevision ?: $article->currentRevision;
        if ($revision->id !== (int) $data['revision_id']) {
            throw ValidationException::withMessages(['article' => 'The article version changed. Reload before generating copy.']);
        }
        if (! app(ProviderCredentials::class)->key('gemini')) {
            throw ValidationException::withMessages(['ai' => 'Add the Gemini free-tier key in Integrations first, or use the ready-to-edit copy below.']);
        }
        try {
            $id = $usage->request($request->user(), $article, $revision->id, 'promotion', 'gemini');
        } catch (UniqueConstraintViolationException) {
            return back()->with('status', 'Promotion copy was already requested for this revision. Existing copy is shown below; no new provider call was made.');
        }
        try {
            $schema = ['type' => 'OBJECT', 'properties' => ['headline' => ['type' => 'STRING'], 'social_post' => ['type' => 'STRING'], 'newsletter_summary' => ['type' => 'STRING']], 'required' => ['headline', 'social_post', 'newsletter_summary']];
            $kit = $assistant->generate(['title' => $revision->title, 'excerpt' => $revision->excerpt, 'article_body' => mb_substr($revision->body_markdown ?? '', 0, 20000)], $schema, 'Create useful sharing copy from this article only: a headline, a social post of at most 500 characters and a newsletter summary of at most 800 characters. Do not claim popularity, personal experience or guaranteed results. Do not add URLs; the application appends the real article link. Do not invent promotional offers or commercial relationships.');
            $kit = Validator::make($kit, ['headline' => ['required', 'string', 'max:180'], 'social_post' => ['required', 'string', 'max:500'], 'newsletter_summary' => ['required', 'string', 'max:800']])->validate();
            DB::table('article_ai_requests')->where('id', $id)->update(['status' => 'completed', 'payload' => json_encode($kit), 'updated_at' => now()]);
            AuditEvent::record($request->user(), 'article.promotion_prepared', $article, ['revision' => $revision->number]);
        } catch (Throwable) {
            DB::table('article_ai_requests')->where('id', $id)->update(['status' => 'failed', 'updated_at' => now()]);
            throw ValidationException::withMessages(['ai' => 'AI copy could not be prepared. Existing article and sharing copy were preserved; no retry or paid fallback was made.']);
        }

        return back()->with('status', 'Sharing copy prepared. Edit or copy it below. Nothing was posted or emailed.');
    }
}
