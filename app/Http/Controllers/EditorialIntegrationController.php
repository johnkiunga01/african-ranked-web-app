<?php

namespace App\Http\Controllers;

use App\Enums\RevisionStatus;
use App\Models\Article;
use App\Models\AuditEvent;
use App\Models\User;
use App\Services\ArticleImageReuseGuard;
use App\Services\EditorialEvidence;
use App\Services\EditorialWorkflow;
use App\Services\PublicationQuality;
use App\Support\Catalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Authenticated editorial integration. Never approves, publishes, assigns or deletes. */
class EditorialIntegrationController extends Controller
{
    public function drafts(Request $request): JsonResponse
    {
        $limit = (int) ($request->validate(['limit' => ['sometimes', 'integer', 'between:1,3']])['limit'] ?? 3);
        $articles = Article::query()->whereNull('trashed_at')->whereNull('published_revision_id')
            ->whereHas('currentRevision', fn ($q) => $q->where('status', RevisionStatus::Draft->value))
            ->whereDoesntHave('revisions', fn ($q) => $q->whereIn('status', [
                RevisionStatus::InReview->value, RevisionStatus::Approved->value, RevisionStatus::Published->value,
            ]))
            ->with(['currentRevision', 'author'])->oldest('updated_at')->orderBy('id')->limit($limit)->get();

        return response()->json(['items' => $articles->map(fn ($article) => [
            'article_id' => $article->id, 'slug' => $article->slug,
            'title' => $article->currentRevision?->title, 'status' => $article->currentRevision?->status?->value,
            'revision_id' => $article->current_revision_id, 'revision_number' => $article->currentRevision?->number,
            'author_name' => $article->author?->name, 'updated_at' => $article->updated_at?->toIso8601String(),
        ])->values(), 'selection' => 'Unpublished drafts without previous review or publication.']);
    }

    public function show(Request $request, Article $article, PublicationQuality $quality): JsonResponse
    {
        Gate::forUser($this->actor($request))->authorize('view', $article);
        abort_if($article->trashed_at || $article->published_revision_id
            || $article->revisions()->whereIn('status', [RevisionStatus::Approved->value, RevisionStatus::Published->value])->exists(), 404);
        $article->load(['currentRevision', 'author']);
        $r = $article->currentRevision;
        abort_unless($r, 404);

        return response()->json([
            'article_id' => $article->id, 'slug' => $article->slug,
            'current_revision_id' => $article->current_revision_id, 'revision_number' => $r->number,
            'status' => $r->status->value,
            'author' => ['name' => $article->author?->name,
                'active_verified' => (bool) ($article->author?->is_active && $article->author?->hasVerifiedEmail())],
            'manuscript' => [
                'title' => $r->title, 'excerpt' => $r->excerpt, 'category_slug' => $r->category_slug,
                'body_markdown' => $r->body_markdown, 'faqs' => $r->faqs,
                'image' => $r->image, 'image_alt' => $r->image_alt, 'source_notes' => $r->source_notes,
                'seo_title' => $r->seo_title, 'meta_description' => $r->meta_description,
                'focus_keyword' => $r->focus_keyword, 'editorial_evidence' => $r->editorial_evidence,
            ],
            'quality' => $quality->inspect($r),
            'revision_history' => $article->revisions()->get(['id', 'number', 'status', 'created_at'])
                ->map(fn ($entry) => [
                    'id' => $entry->id, 'number' => $entry->number, 'status' => $entry->status->value,
                    'created_at' => $entry->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function save(Request $request, Article $article, EditorialWorkflow $workflow): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'expected_revision_id' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:180'],
            'excerpt' => ['required', 'string', 'max:500'],
            'category_slug' => ['required', Rule::in(app(Catalog::class)->categories()->pluck('slug')->all())],
            'body_markdown' => ['required', 'string', 'max:150000'],
            'faqs_text' => ['sometimes', 'nullable', 'string', 'max:30000'],
            'image' => ['sometimes', 'nullable', 'url:https', 'max:2048'],
            'image_alt' => ['sometimes', 'nullable', 'string', 'max:300'],
            'source_notes' => ['sometimes', 'nullable', 'string', 'max:30000'],
            'seo_title' => ['sometimes', 'nullable', 'string', 'max:180'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'focus_keyword' => ['sometimes', 'nullable', 'string', 'max:180'],
            'evidence' => ['required', 'array'],
        ]);
        $data['editorial_evidence'] = app(EditorialEvidence::class)->validate($data['evidence']);
        unset($data['evidence']);

        $saved = DB::transaction(function () use ($article, $actor, $data, $workflow) {
            $locked = Article::lockForUpdate()->findOrFail($article->id);
            $this->assertEditable($locked, $actor);
            if ((int) $locked->current_revision_id !== (int) $data['expected_revision_id']) {
                throw ValidationException::withMessages(['expected_revision_id' => 'Stale CMS revision; reload before editing.']);
            }
            $payload = $data;
            $payload['revision_id'] = $data['expected_revision_id'];
            unset($payload['expected_revision_id']);
            $prior = $locked->currentRevision;
            foreach (['image', 'image_alt', 'source_notes', 'seo_title', 'meta_description', 'focus_keyword'] as $field) {
                if (! array_key_exists($field, $payload)) {
                    $payload[$field] = $prior?->{$field};
                }
            }
            app(ArticleImageReuseGuard::class)->assertNoNewCrossArticleDuplicates($payload, $locked);
            $saved = $workflow->save($actor, $payload, $locked);
            AuditEvent::record($actor, 'article.integration_draft_saved', $saved, [
                'old_revision_id' => $data['expected_revision_id'], 'new_revision_id' => $saved->current_revision_id,
            ]);

            return $saved;
        });

        return response()->json([
            'article_id' => $saved->id, 'slug' => $saved->slug,
            'revision_id' => $saved->current_revision_id,
            'revision_number' => $saved->currentRevision->number,
            'status' => $saved->currentRevision->status->value,
            'quality' => app(PublicationQuality::class)->inspect($saved->currentRevision),
            'preview_url' => route('staff.articles.preview', $saved),
        ], 201);
    }

    public function submit(Request $request, Article $article, EditorialWorkflow $workflow, PublicationQuality $quality): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate(['expected_revision_id' => ['required', 'integer', 'min:1']]);
        $saved = DB::transaction(function () use ($article, $actor, $data, $workflow, $quality) {
            $locked = Article::lockForUpdate()->findOrFail($article->id);
            $this->assertEditable($locked, $actor);
            if ((int) $locked->current_revision_id !== (int) $data['expected_revision_id']) {
                throw ValidationException::withMessages(['expected_revision_id' => 'Stale CMS revision; reload before submission.']);
            }
            $revision = $locked->currentRevision;
            $failures = $quality->inspect($revision)['failures'];
            $dates = collect($revision->editorial_evidence['facts'] ?? [])->pluck('verified_at');
            if (! $dates->max() || ($revision->editorial_evidence['last_verified_at'] ?? null) !== $dates->max()) {
                $failures[] = 'Last verified date must match the latest checked fact date.';
            }
            if (! $locked->author?->is_active || ! $locked->author?->hasVerifiedEmail()
                || preg_match('/^(?:ar team|test account|unassigned author)$/i', trim((string) $locked->author?->name))) {
                $failures[] = 'Assign a named, active, verified human author.';
            }
            if (! filled($revision->image_alt)) {
                $failures[] = 'Add descriptive hero-image alternative text.';
            }
            if ($failures) {
                throw ValidationException::withMessages(['quality' => array_values(array_unique($failures))]);
            }
            $updated = $workflow->transition($actor, $locked, 'submit', $locked->current_revision_id);
            AuditEvent::record($actor, 'article.integration_submitted', $updated,
                ['revision_id' => $updated->current_revision_id]);

            return $updated;
        });
        $saved->load('currentRevision');

        return response()->json([
            'article_id' => $saved->id, 'slug' => $saved->slug,
            'revision_id' => $saved->current_revision_id, 'revision_number' => $saved->currentRevision->number,
            'status' => $saved->currentRevision->status->value,
            'review_url' => route('staff.articles.edit', $saved),
            'message' => 'Submitted to human review; not approved or published.',
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->attributes->get('editorial_actor');
        abort_unless($actor instanceof User, 401);

        return $actor;
    }

    private function assertEditable(Article $article, User $actor): void
    {
        Gate::forUser($actor)->authorize('update', $article);
        if ($article->trashed_at || $article->published_revision_id
            || $article->currentRevision?->status !== RevisionStatus::Draft
            || $article->revisions()->whereIn('status', [
                RevisionStatus::InReview->value, RevisionStatus::Approved->value, RevisionStatus::Published->value,
            ])->exists()) {
            throw ValidationException::withMessages([
                'workflow' => 'Only untouched, unpublished Draft articles may be changed by the integration.',
            ]);
        }
    }
}
