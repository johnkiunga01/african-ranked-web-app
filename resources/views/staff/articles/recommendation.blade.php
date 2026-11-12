@extends('staff.layout')
@section('title','Review improvements')
@section('content')
<div class="mx-auto max-w-screen-xl"><a class="text-sm font-semibold text-amber-800" href="{{ route('staff.articles.edit',$article) }}">← Back to your article</a>
<h1 class="mt-4 text-3xl font-bold">{{ in_array($status,['queued','researching','writing'],true) ? 'Preparing your suggestion' : 'Review your suggested version' }}</h1>
<p class="mt-3 text-sm leading-relaxed text-stone-600">Your original and any live version stay unchanged until you accept and publish.</p>
@if(in_array($status,['queued','researching','writing'],true))
<section class="staff-panel mt-6" data-recommendation-progress data-status-url="{{ route('staff.recommendations.show',[$article,$recommendation]) }}">
<h2 class="text-xl font-bold" role="status" aria-live="polite" data-recommendation-label>{{ match($status) { 'queued'=>'Waiting to start', 'researching'=>'Checking search research', default=>'Preparing clearer wording and structure' } }}</h2>
<ol class="mt-5 list-decimal space-y-3 pl-5 text-sm"><li>Use your saved editorial plan.</li><li>Check or reuse relevant search research.</li><li>Prepare a version for you to review.</li></ol>
<p class="mt-4 text-sm text-stone-500">You can leave this page and come back. We’ll keep the result with your article.</p>
<p class="mt-3 text-sm text-stone-600" data-recommendation-delay hidden></p>
<a class="staff-secondary mt-4" href="{{ route('staff.recommendations.show',[$article,$recommendation]) }}">Check progress</a>
</section>
@elseif($status==='ready' && $draft)
<section class="staff-panel mt-6"><h2 class="text-xl font-bold">What changed</h2><ul class="mt-4 list-disc space-y-2 pl-5 text-sm">@forelse($recommendation->payload['changes'] ?? [] as $change)<li>{{ $change }}</li>@empty<li>No wording changes were recommended. You can keep your original.</li>@endforelse</ul>
<p class="mt-4 text-sm text-stone-500">Based on your saved editorial guidance and search research{{ filled($recommendation->payload['research_date'] ?? null) ? ' from '.\Carbon\Carbon::parse($recommendation->payload['research_date'])->timezone(config('editorial.display_timezone'))->format('j M Y') : '' }}.</p></section>
@if($blockers)<section class="staff-panel mt-5" role="status"><h2 class="text-xl font-bold">Finish these checks before publishing</h2><ul class="mt-4 list-disc space-y-3 pl-5">@foreach($tasks as $task)<li><strong>{{ $task['label'] }}</strong><details class="mt-1 text-sm text-stone-600"><summary>What needs attention?</summary><p class="mt-2">{{ $task['detail'] }}</p></details></li>@endforeach</ul><a href="{{ route('staff.articles.edit',$article) }}#editorial-evidence" class="staff-secondary mt-5">Update your article</a></section>@endif
<div class="mt-6 grid gap-6 lg:grid-cols-2"><section class="staff-panel min-w-0"><h2 class="mb-5 text-xl font-bold text-amber-800">Suggested version</h2>@include('staff.articles.recommendation-preview',['version'=>$proposed])</section><details class="staff-panel min-w-0 self-start"><summary class="cursor-pointer text-lg font-bold text-stone-700">Compare with my original</summary><div class="mt-5">@include('staff.articles.recommendation-preview',['version'=>$source])</div></details></div>
<section class="staff-panel mt-6">@can('publish', $article)<form method="POST" action="{{ route('staff.recommendations.accept',[$article,$recommendation]) }}">@csrf<input type="hidden" name="revision_id" value="{{ $recommendation->source_revision_id }}"><label class="flex items-start gap-3 text-sm leading-relaxed"><input class="mt-1" type="checkbox" name="human_review" value="1" required @disabled(count($blockers)>0)>I’ve reviewed this version and checked its facts, sources, ranking and image permissions.</label><button type="submit" class="staff-button mt-4" @disabled(count($blockers)>0)>Accept and publish</button><p class="mt-2 text-xs text-stone-500">This replaces the live article with the version shown above. Earlier versions remain in history.</p></form>@else<p class="text-sm text-stone-600">Save this suggestion as a private draft, then submit it for another author to review. A super admin handles publication.</p>@endcan<form class="mt-3" method="POST" action="{{ route('staff.recommendations.draft',[$article,$recommendation]) }}">@csrf<input type="hidden" name="revision_id" value="{{ $recommendation->source_revision_id }}"><button class="staff-secondary" type="submit">Continue editing this version</button></form><form class="mt-3" method="POST" action="{{ route('staff.recommendations.discard',[$article,$recommendation]) }}">@csrf<button class="staff-secondary" type="submit">Keep my original</button></form></section>
@elseif($status==='accepted')
<section class="staff-panel mt-6"><h2 class="text-xl font-bold">Your accepted version is published</h2><p class="mt-3">Earlier versions are kept in the article history.</p><a class="staff-button mt-4" href="/articles/{{ $article->slug }}">View article</a></section>
@elseif($status==='draft_saved')
<section class="staff-panel mt-6"><h2 class="text-xl font-bold">Saved for you to finish</h2><p class="mt-3">The suggested version is a private draft. Nothing was published.</p><a class="staff-secondary mt-4" href="{{ route('staff.articles.edit',$article) }}">Continue editing</a></section>
@elseif($status==='discarded')
<section class="staff-panel mt-6"><h2 class="text-xl font-bold">You kept your original</h2><p class="mt-3">Nothing was published from this suggestion.</p></section>
@else
<section class="staff-panel mt-6"><h2 class="text-xl font-bold">{{ $status==='stale' ? 'Your article has changed' : 'We couldn’t finish this suggestion' }}</h2><p class="mt-3">{{ $status==='stale' ? 'Return to the editor and request a suggestion for your latest saved article.' : 'Your article is safe. No partial result was saved and no automatic retry was made. A provider charge may still apply.' }}</p><a class="staff-secondary mt-4" href="{{ route('staff.articles.edit',$article) }}">Return to your article</a></section>
@endif
</div>
@endsection
