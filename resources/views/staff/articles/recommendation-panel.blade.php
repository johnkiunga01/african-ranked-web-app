<section id="article-improvements" class="staff-panel" aria-labelledby="improvement-heading">
<h2 id="improvement-heading" class="text-lg font-bold">Improve this article</h2>
<p class="mt-3 text-sm leading-relaxed text-stone-600">Get a suggested version based on your editorial plan and current search research. Review the changes, then choose whether to publish.</p>
@if($article && isset($recommendation) && $recommendation && !in_array($recommendation->status,['discarded','stale'],true))
<p class="mt-4 text-sm font-semibold">{{ match($recommendation->status) { 'queued'=>'Your suggestion is waiting to start.', 'researching'=>'Checking search research…', 'writing'=>'Preparing your suggested version…', 'ready'=>'Your suggested version is ready to review.', 'accepted'=>'Your accepted version is published.', default=>'We could not finish that suggestion.' } }}</p>
<a class="staff-button mt-4 w-full justify-center" href="{{ route('staff.recommendations.show',[$article,$recommendation]) }}">{{ $recommendation->status==='accepted' ? 'View accepted version' : 'Review progress & changes' }}</a>
@else
<button class="staff-button mt-4 w-full justify-center" type="submit" form="article-editor" name="next_step" value="recommend">Suggest improvements</button>
@endif
<p class="mt-3 text-xs leading-relaxed text-stone-500">Suggestions use your team’s research and AI allowance. Your saved article is shared with the configured providers. Nothing is published until you accept.</p>
</section>