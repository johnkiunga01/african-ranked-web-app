@extends('staff.layout')
@section('title', $article ? 'Edit article' : 'New article')
@section('content')
@if($article && auth()->user()->isSuperAdmin())
<details class="staff-panel mb-6"><summary class="cursor-pointer text-sm font-semibold">Article assignment · {{ $article->author?->name ?? 'Unassigned' }}</summary><form action="{{ route('staff.articles.assign', $article) }}" method="POST" class="mt-4 flex flex-wrap items-end gap-3">@csrf @method('PATCH')<input type="hidden" name="revision_id" value="{{ $revision->id }}"><div><label class="staff-label" for="assigned-author">Assigned author</label><select class="staff-input" name="user_id" id="assigned-author" required>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected($article->user_id === $owner->id)>{{ $owner->name }}</option>@endforeach</select></div><button class="staff-secondary" type="submit">Assign in new draft revision</button><p class="w-full text-xs text-stone-500">Assignment creates a draft requiring a new review. Published content and bylines remain unchanged until approved publication.</p></form></details>
@endif
<div class="flex flex-wrap items-center justify-between gap-4"><div><a href="{{ route('staff.articles.index') }}" class="text-sm text-amber-800">← Articles</a><h1 class="mt-2 text-3xl font-bold">{{ $article ? 'Edit article' : 'New article' }}</h1></div>@if($article)<a href="{{ route('staff.articles.preview', $article) }}" class="staff-secondary">Preview draft</a>@endif</div>
@if($article?->published_revision_id)<p class="mt-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-900">A version is live. Saving here creates a private draft revision; it will not replace the published article.</p>@endif
@if($revision?->review_notes)<aside class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4"><h2 class="text-sm font-bold text-amber-900">Review feedback</h2><p class="mt-2 whitespace-pre-line text-sm text-amber-950">{{ $revision->review_notes }}</p></aside>@endif
<div class="sticky top-[68px] z-20 mt-5 flex flex-wrap items-center gap-2 rounded-2xl border border-stone-200 bg-white/95 p-3 shadow-sm backdrop-blur-lg">
    <button class="staff-button min-h-11" type="submit" form="article-editor">{{ $article ? 'Save private draft' : 'Create draft' }}</button>
    @if($article)
        <a class="staff-secondary min-h-11" href="{{ route('staff.articles.preview', $article) }}">Preview</a>
        @can('publish', $article)
            @if($revision->status->value !== 'published')
                <a class="staff-secondary min-h-11" href="{{ route('staff.articles.publish-form', $article) }}">{{ $article->published_revision_id ? 'Review changes' : 'Review & publish' }}</a>
            @else
                <a class="staff-secondary min-h-11" href="/articles/{{ $article->slug }}">View published</a>
            @endif
        @endcan
        <details class="relative ml-auto">
            <summary class="inline-flex min-h-11 cursor-pointer items-center gap-1 rounded-xl px-3 text-sm font-bold text-stone-600 hover:bg-amber-50 hover:text-stone-950">More <x-icon name="chevron-down" class="h-4 w-4" /></summary>
            <nav aria-label="Article editing tools" class="absolute right-0 z-30 mt-2 w-56 space-y-1 rounded-2xl border border-stone-200 bg-white p-2 shadow-xl">
                <a class="block rounded-xl px-3 py-2 text-sm font-semibold text-stone-700 hover:bg-amber-50" href="{{ route('staff.articles.seo', $article) }}">SEO tools</a>
                <a class="block rounded-xl px-3 py-2 text-sm font-semibold text-stone-700 hover:bg-amber-50" href="#ai-assistant">Writing assistant</a>
                <a class="block rounded-xl px-3 py-2 text-sm font-semibold text-stone-700 hover:bg-amber-50" href="#editorial-evidence">Editorial evidence</a>
                <a class="block rounded-xl px-3 py-2 text-sm font-semibold text-stone-700 hover:bg-amber-50" href="{{ route('staff.articles.promotion', $article) }}">Promotion tools</a>
            </nav>
        </details>
    @endif
</div>

<div class="mt-7 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
<form id="article-editor" data-article-editor data-media-upload-url="{{ route('staff.articles.media.upload') }}" data-article-id="{{ $article?->id }}" class="staff-panel space-y-5" method="POST" action="{{ $article ? route('staff.articles.update', $article) : route('staff.articles.store') }}">@csrf @if($article)@method('PUT')<input name="revision_id" type="hidden" value="{{ old('revision_id', $revision?->id) }}">@endif
<div><label class="staff-label" for="title">Article title</label><input class="staff-input" id="title" name="title" required maxlength="180" value="{{ old('title', $revision?->title) }}"></div>
@if(!$article)<div><label class="staff-label" for="slug">Page address</label><div class="mb-1 text-xs text-stone-500">/articles/your-article-title</div><input class="staff-input" id="slug" name="slug" required maxlength="180" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{{ old('slug') }}"><p class="mt-1 text-xs text-stone-500">Lowercase words separated by hyphens. The address stays stable after creation.</p></div>@else<p class="text-xs text-stone-500">Page address: /articles/{{ $article->slug }}</p>@endif
<div><label class="staff-label" for="excerpt">Short introduction</label><textarea class="staff-input" id="excerpt" name="excerpt" rows="3" required maxlength="500">{{ old('excerpt', $revision?->excerpt) }}</textarea></div>
<div><label class="staff-label" for="category_slug">Category</label><select class="staff-input" name="category_slug" id="category_slug" required>@foreach($categories as $category)<option value="{{ $category['slug'] }}" @selected(old('category_slug', $revision?->category_slug) === $category['slug'])>{{ $category['name'] }}</option>@endforeach</select></div>
<div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-bold text-stone-900">Write your article</h2><p class="text-sm text-stone-600">Write naturally, then add headings and photos with the toolbar. Your original is saved as a private draft.</p></div><button type="button" class="staff-secondary" data-editor-preview-toggle aria-controls="article-writing-preview" aria-expanded="false">Preview formatting</button></div>
<div><label class="staff-label" for="body_markdown">Article body</label><p id="body-help" class="mb-2 text-xs leading-relaxed text-stone-500">Write paragraphs as normal. Use the toolbar for formatting; ## creates section headings, ### for subheadings, and - for list items. Standalone links use [label](https://source.example/page) or [label](/articles/related-article). Text is rendered safely; pasted HTML does not execute.</p><div class="mb-2 flex flex-wrap gap-2" aria-label="Article formatting tools"><button type="button" class="staff-secondary" data-insert-block="## Section heading">H2 section</button><button type="button" class="staff-secondary" data-insert-block="### Subheading">H3 section</button><button type="button" class="staff-secondary" data-insert-block="- List item">List</button><button type="button" class="staff-secondary" data-insert-block="[Related reading](/articles/related-article)">Link</button><button type="button" class="staff-secondary" data-editor-add-photo>Add photo</button></div><textarea class="staff-input min-h-[420px]" id="body_markdown" name="body_markdown" rows="18" required aria-describedby="body-help">{{ old('body_markdown', $revision?->body_markdown) }}</textarea></div>
<section class="rounded-2xl border border-stone-200 bg-stone-50 p-4 sm:p-5" aria-labelledby="media-composer-heading" data-media-composer>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 id="media-composer-heading" class="text-lg font-bold text-stone-950">Add photos to your story</h2>
            <p class="mt-1 max-w-lg text-sm leading-relaxed text-stone-600">Upload your own images, see a preview, then choose <strong>Set as cover</strong> or <strong>Insert into article</strong>. Drop a photo here or paste one while editing.</p>
        </div>
        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">JPG · PNG · WebP · 8 MB max</span>
    </div>
    <div class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <label for="article-photo-file" data-media-dropzone class="flex min-h-44 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-stone-300 bg-white px-4 py-6 text-center transition hover:border-amber-500 hover:bg-amber-50 focus-within:border-amber-600">
            <x-icon name="image" class="mb-3 h-9 w-9 text-amber-700" />
            <span class="font-semibold text-stone-900">Choose a photo</span>
            <span class="mt-1 text-sm text-stone-500">or drag and drop it here</span>
            <input id="article-photo-file" data-media-file type="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
        </label>
        <div class="flex min-h-44 items-center justify-center overflow-hidden rounded-2xl border border-stone-200 bg-white" data-media-preview-wrap>
            @if(old('image', $revision?->image))
                <img data-media-preview src="{{ old('image', $revision?->image) }}" alt="Current article cover preview" class="h-44 w-full object-cover">
            @else
                <div data-media-empty class="px-5 text-center text-sm text-stone-500">Your image preview will appear here.</div>
                <img data-media-preview alt="Selected photo preview" class="hidden h-44 w-full object-cover">
            @endif
        </div>
    </div>
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <label class="staff-label" for="image_alt">Describe the image</label>
            <input class="staff-input" id="image_alt" name="image_alt" maxlength="300" placeholder="e.g. Elephants walking beside a swamp in Amboseli" value="{{ old('image_alt', $revision?->image_alt) }}" data-media-alt>
            <p class="mt-1 text-xs text-stone-500">A natural description helps accessibility and image SEO.</p>
        </div>
        <div>
            <label class="staff-label" for="media-credit">Photo credit (optional)</label>
            <input class="staff-input" id="media-credit" data-media-credit maxlength="180" placeholder="Photographer or your organisation">
        </div>
    </div>
    <div class="mt-4">
        <label class="staff-label" for="media-caption">Caption (optional)</label>
        <input class="staff-input" id="media-caption" data-media-caption maxlength="500" placeholder="Explain what the reader is seeing">
    </div>
    <label class="mt-4 flex items-start gap-2 rounded-xl bg-white px-3 py-3 text-sm text-stone-700">
        <input data-media-rights type="checkbox" class="mt-1 accent-amber-600">
        <span>I own this photo or have permission to publish it. I confirm it doesn't misrepresent the article or imply a person's endorsement.</span>
    </label>
    <div class="mt-4 flex flex-wrap gap-2">
        <button type="button" data-media-as-cover class="staff-button" disabled>Set as cover</button>
        <button type="button" data-media-insert class="staff-secondary" disabled>Insert into article</button>
        <button type="button" data-media-clear class="staff-secondary" hidden>Choose another</button>
    </div>
    <p role="status" aria-live="polite" data-media-status class="mt-3 text-sm text-stone-600">Choose a file and confirm permission to upload it.</p>
    <details class="mt-4 border-t border-stone-200 pt-3">
        <summary class="cursor-pointer text-xs font-semibold text-stone-600">Advanced: use a licensed external image URL</summary>
        <div class="mt-3">
            <label class="staff-label" for="image">Cover image URL</label>
            <input class="staff-input" id="image" name="image" type="url" placeholder="https://…" value="{{ old('image', $revision?->image) }}" data-media-url>
            <p class="mt-1 text-xs text-stone-500">Use only images you have permission to publish. Uploading is recommended.</p>
        </div>
    </details>
</section>
<section class="rounded-xl border border-stone-200 p-4"><h2 class="font-bold">Search appearance & intent</h2><div class="mt-4 space-y-4">@foreach(['seo_title' => ['Search title override', 180], 'meta_description' => ['Search description override', 500], 'focus_keyword' => ['Research target query', 180]] as $field => [$label, $limit])<div><label class="staff-label" for="{{ $field }}">{{ $label }}</label><input class="staff-input" id="{{ $field }}" name="{{ $field }}" maxlength="{{ $limit }}" value="{{ old($field, $revision?->$field) }}" data-character-count><p class="mt-1 text-xs text-stone-500" data-count-for="{{ $field }}"></p></div>@endforeach</div><p class="mt-3 text-xs text-stone-500">Blank overrides use the article title and introduction. Target queries are editorial notes, not verified keyword metrics. Metadata remains private until approved publication.</p></section>
<div><label class="staff-label" for="faqs_text">Reader FAQs</label><p id="faq-help" class="mb-2 text-xs leading-relaxed text-stone-500">Question on the first line, answer on the following lines. Separate each FAQ with a blank line. Up to 20 FAQs. These will be visible in the article and reflected in matching schema.</p><textarea class="staff-input" rows="8" id="faqs_text" name="faqs_text" aria-describedby="faq-help">{{ old('faqs_text', collect($revision?->faqs ?? [])->map(fn($faq) => $faq['question']."\n".$faq['answer'])->implode("\n\n")) }}</textarea></div>
<fieldset class="rounded-xl border border-stone-200 p-4"><legend class="font-semibold">Editions</legend><input type="hidden" name="all_editions" value="0"><label class="mt-2 flex gap-2 text-sm"><input type="checkbox" name="all_editions" value="1" @checked(old('all_editions',$revision?->regions===null))>All current and future editions</label><p class="mt-2 text-xs text-stone-500">The global catalogue includes every published article. Select regional editions below only when not using all editions.</p><div class="mt-3 grid gap-2 sm:grid-cols-2">@foreach($editions as $code=>$edition)@if($code!=='global')<label class="flex gap-2 text-sm"><input type="checkbox" name="regions[]" value="{{ $code }}" @checked(in_array($code,old('regions',$revision?->regions ?? []),true))>{{ $edition['flag'] }} {{ $edition['name'] }}</label>@endif @endforeach</div></fieldset>
@include('staff.articles.evidence')
<div><label class="staff-label" for="source_notes">Sources and research notes</label><textarea class="staff-input" id="source_notes" name="source_notes" rows="5" placeholder="Record source URLs, verification dates, ranking criteria and image permission details.">{{ old('source_notes', $revision?->source_notes) }}</textarea></div>
<p class="text-xs text-stone-500">Save your changes using the action bar above. Published content changes only after approval.</p>
</form>
<aside class="space-y-5">
@if($article && auth()->user()->can('curate',$article))@php($draftProviders = collect(['openai' => 'OpenAI', 'gemini' => 'Google Gemini'])->filter(fn($label, $key) => filled(app(\App\Services\ProviderCredentials::class)->key($key))))<details id="ai-assistant" class="staff-panel"><summary class="cursor-pointer font-bold">AI editorial assistant</summary><p class="mt-3 text-xs leading-relaxed text-stone-500">Uses the saved target query, draft, source notes and matching imported Search Console page data. Saved OpenSEO keyword and competitor research is attached automatically when available. Run research-led optimisation in the SEO workspace first. Output always becomes a private revision requiring review.</p><p class="mt-2 text-xs text-stone-500">{{ $draftProviders->isNotEmpty() ? 'Choose a configured provider below.' : 'Ask the administrator to configure a drafting provider under Integrations.' }}</p><form method="POST" action="{{ route('staff.articles.curate', $article) }}" class="mt-4 space-y-3" data-curation-form>@csrf<input type="hidden" name="revision_id" value="{{ $revision->id }}"><label class="staff-label" for="draft-provider">Drafting provider</label><select class="staff-input" id="draft-provider" name="provider" required>@foreach($draftProviders as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select><label class="staff-label" for="curation-brief">Editorial brief</label><textarea class="staff-input" id="curation-brief" name="brief" rows="5" minlength="30" maxlength="10000" required placeholder="Describe the reader intent, evidence to use, structure and improvements needed."></textarea><label class="flex items-start gap-2 text-xs leading-relaxed"><input type="checkbox" name="confirm_external" value="1" required class="mt-1">Send this saved draft, source notes, brief and matching report data to the selected provider. Provider usage charges apply. I will verify the output before publication.</label><button class="staff-button w-full" type="submit" @disabled($draftProviders->isEmpty())>Generate private draft</button><p class="text-xs text-stone-500">One request per saved revision; no automatic retry. Save editor changes first. Generation may take up to a minute.</p></form></details>@endif

@if($article)<section class="staff-panel"><h2 class="font-bold">SEO & content checks</h2><p class="mt-3 text-sm text-stone-600">{{ $analysis['wordCount'] }} body words · {{ $analysis['headingCount'] }} headings · {{ $analysis['faqCount'] }} FAQs</p><p class="mt-2 text-sm text-stone-600">{{ count($analysis['findings']) }} findings on the saved revision.</p><a href="{{ route('staff.articles.seo', $article) }}" class="staff-secondary mt-4">Open SEO analysis</a><p class="mt-3 text-xs text-stone-500">Save changes to refresh findings. No ranking score or external keyword demand is inferred.</p></section>@endif

<div class="staff-panel"><h2 class="text-lg font-bold">Review & publication</h2>@if($revision)<p class="mt-3 text-sm text-stone-600">Revision {{ $revision->number }} · <span class="font-semibold">{{ str_replace('_', ' ', $revision->status->value) }}</span></p><p class="mt-2 text-xs text-stone-500">Save your changes before submitting. Actions below apply to this saved revision only.</p>
@if(in_array($revision->status, [\App\Enums\RevisionStatus::Draft, \App\Enums\RevisionStatus::ChangesRequested], true))<form class="mt-5" method="POST" action="{{ route('staff.articles.action', [$article, 'submit']) }}">@csrf<input name="revision_id" type="hidden" value="{{ $revision->id }}"><button class="staff-button w-full" type="submit">Submit for review</button></form>@endif
@can('review', $article)
@if($revision->status === \App\Enums\RevisionStatus::InReview)<form class="mt-5 space-y-3" method="POST" action="{{ route('staff.articles.action', [$article, 'approve']) }}">@csrf<input name="revision_id" type="hidden" value="{{ $revision->id }}"><p class="text-xs font-semibold text-stone-700">Approve only after checking:</p>@foreach(['facts' => 'Facts and sources verified', 'ranking' => 'Content and promised ranking complete', 'attribution' => 'Author and attribution confirmed', 'image_rights' => 'Image permission confirmed'] as $key => $label)<label class="flex items-start gap-2 text-xs leading-relaxed text-stone-600"><input type="checkbox" name="{{ $key }}" value="1" required class="mt-0.5 accent-amber-600">{{ $label }}</label>@endforeach<label for="approve_notes" class="staff-label">Review notes (optional)</label><textarea id="approve_notes" class="staff-input" name="review_notes" rows="3"></textarea><button class="staff-button w-full" type="submit">Approve this revision</button></form>@endif
@if(in_array($revision->status, [\App\Enums\RevisionStatus::InReview, \App\Enums\RevisionStatus::Approved], true))<form class="mt-5 space-y-3 border-t border-stone-100 pt-5" method="POST" action="{{ route('staff.articles.action', [$article, 'request_changes']) }}">@csrf<input name="revision_id" type="hidden" value="{{ $revision->id }}"><label for="review_notes" class="staff-label">Feedback for the author</label><textarea id="review_notes" name="review_notes" rows="3" class="staff-input" required></textarea><button class="staff-secondary w-full" type="submit">Request changes</button></form>@endif
@endcan
@can('publish', $article)
@if($revision->status === \App\Enums\RevisionStatus::Approved)<form class="mt-5" method="POST" action="{{ route('staff.articles.action', [$article, 'publish']) }}">@csrf<input name="revision_id" type="hidden" value="{{ $revision->id }}"><button type="submit" class="staff-button w-full">Publish approved revision</button></form>@endif
@if($article->published_revision_id)<form class="mt-5 border-t border-stone-100 pt-5" method="POST" action="{{ route('staff.articles.action', [$article, 'unpublish']) }}">@csrf<input name="revision_id" type="hidden" value="{{ $revision->id }}"><button type="submit" class="staff-secondary w-full text-red-700">Withdraw from public site</button></form>@endif
@endcan
@else<p class="mt-3 text-sm text-stone-500">Save your first draft to preview and submit it.</p>@endif</div>
@if($article)<div class="staff-panel"><h2 class="font-bold">Revision history</h2><ol class="mt-3 space-y-3 text-xs text-stone-500">@foreach($history as $version)<li><span class="font-semibold text-stone-700">Version {{ $version->number }}</span> · {{ str_replace('_', ' ', $version->status->value) }}<br>{{ $version->created_at->timezone(config('editorial.display_timezone'))->format('M j, Y H:i T') }}<br><a class="font-semibold text-amber-800 underline" href="{{ route('staff.articles.revision', [$article, $version->id]) }}">Compare / restore</a></li>@endforeach</ol><div class="mt-4">{{ $history->links('staff.pagination', ['label' => 'Revision history pages']) }}</div></div>@endif
</aside></div>
@endsection
