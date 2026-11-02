@extends('staff.layout')
@section('title', 'Article SEO workspace')
@section('content')
<nav class="flex flex-wrap gap-3 text-xs text-stone-500" aria-label="Breadcrumb"><a class="text-amber-800" href="{{ route('staff.articles.index') }}">Articles</a><span>/</span><a class="text-amber-800" href="{{ route('staff.articles.edit', $article) }}">Editor</a><span>/ SEO</span></nav>
<div class="mt-5 flex flex-wrap items-start justify-between gap-4">
    <div class="max-w-2xl"><p class="text-xs text-stone-500">Saved revision {{ $revision->number }} · {{ ucfirst(str_replace('_',' ',$revision->status->value)) }}</p><h1 class="mt-2 text-2xl font-bold">SEO workspace</h1><p class="mt-2 text-sm leading-relaxed text-stone-600">{{ $revision->title }}</p></div>
    <div class="flex flex-wrap gap-2"><a class="staff-secondary" href="{{ route('staff.articles.preview', $article) }}">Preview draft</a>
    @can('publish', $article)
        <a class="staff-secondary" href="{{ route('staff.articles.publish-form', $article) }}">{{ $article->published_revision_id ? 'Publish changes…' : 'Publish article…' }}</a>
    @endcan
    </div>
</div>
<div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl border border-stone-200 bg-white px-5 py-4 text-sm"><span><strong>{{ $analysis['errors'] }}</strong> missing essentials</span><span><strong>{{ count($analysis['findings']) - $analysis['errors'] }}</strong> improvement checks</span><span class="text-xs text-stone-500">{{ $analysis['wordCount'] }} words · {{ $analysis['headingCount'] }} headings · {{ $analysis['linkCount'] }} links · {{ $analysis['faqCount'] }} FAQs</span></div>
<div class="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
    <div class="contents xl:order-1 xl:block xl:space-y-6">
        <section class="staff-panel order-1 border-amber-200">
            <p class="text-xs font-semibold uppercase tracking-widest text-amber-800">Research-led optimisation</p><h2 class="mt-2 text-xl font-bold">Give Gemini an evidence-based brief</h2><p class="mt-3 text-sm leading-relaxed text-stone-600">OpenSEO researches the target query and current search results. Gemini uses that data to write a clearer search title and description, saved as a new draft.</p>
            @can('curate', $article)
                <form class="mt-5" method="POST" action="{{ route('staff.articles.bulk') }}">
                    @csrf
                    <input type="hidden" name="operation" value="gemini"><input type="hidden" name="selected[{{ $article->id }}]" value="{{ $revision->id }}"><button class="staff-button" type="submit">Research & optimise with Gemini</button>
                </form>
            @else
                <p class="mt-4 text-sm text-stone-600">Ask your administrator to enable AI assistance for authors.</p>
            @endcan
            <p class="mt-3 text-xs leading-relaxed text-stone-500">Uses OpenSEO credits for missing research and your Gemini quota. Research is reused for 30 days. This improves search metadata; it does not rewrite the full article.</p>
            <div class="mt-5 border-t border-stone-100 pt-4"><div class="flex flex-wrap items-center justify-between gap-4"><div class="max-w-sm"><h3 class="text-sm font-semibold">Quick fixes without AI</h3><p class="mt-1 text-xs leading-relaxed text-stone-500">Trim long metadata, infer a candidate query from the headline and add available related reading. No keyword or competitor research.</p></div><form method="POST" action="{{ route('staff.articles.seo-fix', $article) }}" data-seo-action>@csrf<input type="hidden" name="revision_id" value="{{ $revision->id }}"><input type="hidden" name="provider" value="local"><button class="staff-secondary" type="submit">Apply rule-based fixes</button></form></div></div>
        </section>
        <section class="staff-panel order-3">
            <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-bold">What still needs attention</h2><span class="text-xs text-stone-500">{{ count($analysis['findings']) }} checks</span></div>
            <ol class="mt-4 divide-y divide-stone-100">
            @forelse($analysis['findings'] as $finding)
                @php
                    $field = match($finding['label']) {
                        'Search title may truncate' => 'seo_title',
                        'Search description missing', 'Search description is long' => 'meta_description',
                        'Search intent not recorded', 'Target phrase is absent' => 'focus_keyword',
                        'Image description needs review' => 'image_alt',
                        'Research evidence needs review' => 'source_notes',
                        'Hero image missing' => 'image',
                        default => 'body_markdown',
                    };
                    $metadata = in_array($field, ['seo_title','meta_description','focus_keyword','image_alt'], true);
                    $automatic = in_array($field, ['seo_title','meta_description','focus_keyword'], true);
                    $href = $metadata ? '#search-metadata' : route('staff.articles.edit', $article).'#'.$field;
                    if ($finding['label'] === 'Editorial approval outstanding' && auth()->user()->can('publish', $article)) $href = route('staff.articles.publish-form', $article);
                    if ($finding['label'] === 'No assigned author') $href = route('staff.articles.edit', $article).'#assigned-author';
                @endphp
                <li class="py-5"><div class="flex flex-wrap items-center justify-between gap-2"><span class="text-[11px] font-semibold uppercase tracking-wide {{ $finding['severity'] === 'error' ? 'text-red-800' : 'text-stone-500' }}">{{ $finding['severity'] === 'error' ? 'Missing essential' : ($automatic ? 'Metadata improvement' : 'Editorial review') }}</span><a class="text-xs font-semibold text-amber-800" href="{{ $href }}" aria-label="{{ $finding['label'] }}: open the relevant editor">{{ $finding['label'] === 'Editorial approval outstanding' && auth()->user()->can('publish', $article) ? 'Review & publish' : 'Edit this' }} →</a></div><h3 class="mt-2 text-sm font-semibold">{{ $finding['label'] }}</h3><p class="mt-2 break-words text-xs leading-relaxed text-stone-600">{{ $finding['detail'] }}</p></li>
            @empty
                <li class="py-5 text-sm text-green-900">No automated findings. Complete editorial review before publishing.</li>
            @endforelse
            </ol>
        </section>
        <details class="staff-panel order-4"><summary class="cursor-pointer text-sm font-semibold">Analysis scope and limitations</summary><p class="mt-4 text-xs leading-relaxed text-stone-500">Checks the saved working revision. It does not validate facts, image rights, originality, Core Web Vitals or future rankings. OpenSEO metrics are estimates for the stated market and date. Competitor snippets are research leads, not verified source material. Neither tool approves or publishes an article. Metadata length is a display heuristic, not a ranking rule.</p></details>
    </div>
    <aside class="order-2 min-w-0 space-y-6" aria-label="Research and search metadata">
        <section class="staff-panel !p-5"><div class="flex flex-wrap items-start justify-between gap-3"><h2 class="font-bold">OpenSEO brief</h2><span class="staff-badge">{{ $research ? 'Available' : 'Not researched' }}</span></div>
        @if($research)
            <p class="mt-4 text-sm font-semibold">{{ $research->keyword }}</p><p class="mt-2 text-xs leading-relaxed text-stone-500">{{ $research->brief['market']['scope'] }} · {{ $research->researched_at->format('M j, Y') }}</p>
            <dl class="mt-4 grid grid-cols-2 gap-4 text-xs">
            @foreach(['search_volume' => 'Monthly searches', 'keyword_difficulty' => 'Keyword difficulty', 'intent' => 'Search intent'] as $key => $label)
                <div><dt class="text-stone-500">{{ $label }}</dt><dd class="mt-1 font-semibold">{{ $research->brief['metrics'][$key] ?? 'Not reported' }}</dd></div>
            @endforeach
            </dl>
            <details class="mt-5 border-t border-stone-100 pt-4"><summary class="cursor-pointer text-xs font-semibold text-amber-800">Search competitors & observations</summary><ol class="mt-3 space-y-4">
            @foreach(array_slice($research->brief['serp_results'] ?? [], 0, 5) as $competitor)
                <li class="text-xs"><p class="font-semibold">{{ $competitor['title'] ?: 'Untitled search result' }}</p><p class="mt-1 text-stone-500">{{ ($competitor['domain'] ?? '') === 'google.com' ? 'Google redirect; destination not confirmed' : ($competitor['domain'] ?? 'Domain not reported') }} · Organic position {{ $competitor['organicRank'] ?? 'unknown' }}</p><p class="mt-1 leading-relaxed text-stone-600">{{ $competitor['description'] ?? '' }}</p></li>
            @endforeach
            </ol><p class="mt-4 text-xs leading-relaxed text-stone-500">SERP snippets only. Full competitor pages and claims have not been verified.</p></details>
        @else
            <p class="mt-4 text-xs leading-relaxed text-stone-600">Run research-led optimisation to attach keyword metrics and search competitors. A target typed into the editor is a candidate until researched.</p>
        @endif
        </section>
        <section class="staff-panel !p-5" id="search-metadata"><h2 class="font-bold">Search appearance preview</h2><p class="mt-4 break-all text-[11px] text-stone-500">{{ $analysis['canonical'] }}</p><p class="mt-2 break-words text-lg font-semibold leading-snug text-blue-900">{{ $analysis['title'] }}</p><p class="mt-2 break-words text-xs leading-relaxed text-stone-600">{{ $analysis['description'] }}</p><p class="mt-3 text-[11px] leading-relaxed text-stone-400">Search engines can choose a different title or snippet.</p>
            <details class="mt-5 border-t border-stone-100 pt-4" @if($errors->hasAny(['seo_title','meta_description','focus_keyword','image_alt'])) open @endif><summary class="cursor-pointer text-xs font-semibold text-amber-800">Edit search metadata</summary><form class="mt-4 space-y-4" method="POST" action="{{ route('staff.articles.seo-metadata', $article) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="revision_id" value="{{ $revision->id }}">
                <div><label class="staff-label" for="seo-title">Search title</label><input class="staff-input" id="seo-title" name="seo_title" maxlength="180" value="{{ old('seo_title', $revision->seo_title) }}"></div>
                <div><label class="staff-label" for="meta-description">Search description</label><textarea class="staff-input" rows="4" id="meta-description" name="meta_description" maxlength="500">{{ old('meta_description', $revision->meta_description) }}</textarea></div>
                <div><label class="staff-label" for="focus-keyword">Target query</label><input class="staff-input" id="focus-keyword" name="focus_keyword" maxlength="180" value="{{ old('focus_keyword', $revision->focus_keyword) }}"></div>
                <div><label class="staff-label" for="image-alt">Image description</label><input class="staff-input" id="image-alt" name="image_alt" maxlength="300" value="{{ old('image_alt', $revision->image_alt) }}"><p class="mt-1 text-xs text-stone-500">Describe the actual image after checking it in the preview.</p></div>
                <button class="staff-secondary w-full" type="submit">Save metadata draft</button>
            </form></details>
        </section>
        <p class="px-1 text-xs leading-relaxed text-stone-500">{{ $article->published_revision_id ? 'A live revision exists. These checks and edits apply to the working draft.' : 'Private draft. Publish after editorial review to make it available on the site.' }}</p>
    </aside>
</div>
@endsection
