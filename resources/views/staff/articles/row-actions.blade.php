@if($article->trashed_at)
    <form method="POST" action="{{ route('staff.articles.lifecycle', [$article, 'restore']) }}">
        @csrf
        <input type="hidden" name="revision_id" value="{{ $article->current_revision_id }}">
        <button type="submit" class="text-xs font-bold text-amber-800 hover:underline">Restore draft</button>
    </form>
@else
    <a class="text-xs font-extrabold text-amber-800 hover:underline" href="{{ route('staff.articles.edit', $article) }}">Edit</a>
    <a class="text-xs font-semibold text-stone-600 hover:text-amber-800 hover:underline" href="{{ route('staff.articles.preview', $article) }}">Preview</a>
    @can('publish', $article)
        @if($article->currentRevision?->status?->value !== 'published')
            <a class="text-xs font-extrabold text-green-800 hover:underline" href="{{ route('staff.articles.publish-form', $article) }}">Review &amp; publish</a>
        @endif
    @endcan
    <details class="w-full pt-1">
        <summary class="inline-flex min-h-8 cursor-pointer items-center gap-1 text-xs font-bold text-stone-500 hover:text-amber-800">
            More actions <x-icon name="chevron-down" class="h-3.5 w-3.5" />
        </summary>
        <div class="mt-2 flex flex-wrap items-center gap-3 rounded-xl border border-stone-200 bg-stone-50 p-3 text-xs">
            <a class="font-semibold text-amber-800 hover:underline" href="{{ route('staff.articles.seo', $article) }}">SEO tools</a>
            <a class="font-semibold text-amber-800 hover:underline" href="{{ route('staff.articles.edit', $article) }}#ai-assistant">Writing assistant</a>
            <a class="font-semibold text-amber-800 hover:underline" href="{{ route('staff.articles.promotion', $article) }}">Promote</a>
            <details class="w-full">
                <summary class="inline-flex min-h-8 cursor-pointer items-center font-bold text-stone-700 hover:text-amber-800">Quick edit title and intro</summary>
                <form class="mt-3 grid gap-3 rounded-xl border border-stone-200 bg-white p-3" method="POST" action="{{ route('staff.articles.quick-edit', $article) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="revision_id" value="{{ $article->current_revision_id }}">
                    <label class="staff-label" for="quick-title-{{ $article->id }}">Title</label>
                    <input class="staff-input" id="quick-title-{{ $article->id }}" name="title" required maxlength="180" value="{{ $article->currentRevision?->title }}">
                    <label class="staff-label" for="quick-intro-{{ $article->id }}">Introduction</label>
                    <textarea class="staff-input" id="quick-intro-{{ $article->id }}" name="excerpt" required maxlength="500">{{ $article->currentRevision?->excerpt }}</textarea>
                    <label class="staff-label" for="quick-category-{{ $article->id }}">Category</label>
                    <select class="staff-input" id="quick-category-{{ $article->id }}" name="category_slug">
                        @foreach($categories as $category)
                            <option value="{{ $category['slug'] }}" @selected($article->currentRevision?->category_slug === $category['slug'])>{{ $category['name'] }}</option>
                        @endforeach
                    </select>
                    <button class="staff-button justify-self-start" type="submit">Save private draft</button>
                </form>
            </details>
            @can('publish', $article)
            <form method="POST" action="{{ route('staff.website.feature', $article) }}">
                @csrf
                <input type="hidden" name="revision_id" value="{{ $article->current_revision_id }}">
                <input type="hidden" name="version" value="{{ $homepageSelection['version'] }}">
                <button class="font-semibold text-amber-800 hover:underline" type="submit">{{ (int)($homepageSelection['value']['featured']['global'] ?? 0) === $article->id ? 'Remove from homepage' : 'Feature on homepage' }}</button>
            </form>
            @endcan
            @can('trash', $article)
            <form method="POST" action="{{ route('staff.articles.lifecycle', [$article, 'trash']) }}">
                @csrf
                <input type="hidden" name="revision_id" value="{{ $article->current_revision_id }}">
                <button class="font-semibold text-red-700 hover:underline" type="submit">Move to trash</button>
            </form>
            @endcan
        </div>
    </details>
@endif
