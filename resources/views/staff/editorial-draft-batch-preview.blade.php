@extends('staff.layout')
@section('title', 'Review bulk private drafts')
@section('content')
<a href="{{ route('staff.editorial-plan') }}" class="text-sm text-stone-600 underline">Back to editorial plan</a>
<h1 class="mt-4 text-3xl font-bold">Review private draft creation</h1>
<p class="mt-3 text-stone-600">{{ $filename }} · {{ $inspection['total'] }} imported editorial briefs</p>
<div class="staff-panel mt-6">
    <h2 class="text-lg font-bold">Duplicate checks and eligibility</h2>
    <div class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
        <div><span class="block text-stone-500">Ready for a new draft</span><strong class="text-2xl">{{ $inspection['counts']['ready'] }}</strong></div>
        <div><span class="block text-stone-500">Existing CMS articles</span><strong class="text-2xl">{{ $inspection['counts']['existing'] }}</strong></div>
        <div><span class="block text-stone-500">References another article</span><strong class="text-2xl">{{ $inspection['counts']['related'] }}</strong></div>
        <div><span class="block text-stone-500">Held by editorial policy</span><strong class="text-2xl">{{ $inspection['counts']['hold'] }}</strong></div>
        <div><span class="block text-stone-500">No team draft</span><strong class="text-2xl">{{ $inspection['counts']['no_draft'] }}</strong></div>
        <div><span class="block text-stone-500">Already processing</span><strong class="text-2xl">{{ $inspection['counts']['active'] }}</strong></div>
    </div>
    <p class="mt-4 text-sm text-stone-700">Only ready entries will create a CMS article with <strong>draft</strong> revision status. This action never submits, approves, publishes, overwrites a manuscript or creates a duplicate URL. Research briefs are not fact-checked articles.</p>
</div>
<div class="staff-panel mt-5 max-h-96 overflow-auto">
    <table class="w-full text-left text-sm">
        <caption class="sr-only">Private draft eligibility for every imported brief</caption>
        <thead><tr><th class="p-2">Plan ID</th><th class="p-2">Title</th><th class="p-2">Action</th></tr></thead>
        <tbody>
            @foreach($inspection['items'] as $item)
                <tr class="border-t border-stone-200">
                    <td class="p-2">{{ $item['brief']->plan_id }}</td>
                    <td class="p-2">{{ $item['brief']->title }}</td>
                    <td class="p-2">
                        @switch($item['status'])
                            @case('ready') Create private draft @break
                            @case('existing') Skip — existing article @break
                            @case('related') Skip — related to existing article @break
                            @case('hold') Skip — editorial hold @break
                            @case('no_draft') Skip — no team draft @break
                            @default Skip — already processing
                        @endswitch
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if($inspection['counts']['ready'] > 0)
    <form class="staff-panel mt-5" method="POST" action="{{ route('staff.editorial-plan.draft-batches.queue') }}">
        @csrf
        <input type="hidden" name="source_hash" value="{{ $sourceHash }}">
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="confirm_private" value="1" required>
            <span>I have reviewed the eligibility list and authorise creation of only the {{ $inspection['counts']['ready'] }} eligible private CMS drafts. No review, approval or publication is authorised.</span>
        </label>
        <div class="mt-4 flex gap-3">
            <button class="staff-button">Queue {{ $inspection['counts']['ready'] }} private drafts</button>
            <a class="staff-secondary" href="{{ route('staff.editorial-plan') }}">Cancel</a>
        </div>
    </form>
@else
    <p class="mt-5 text-sm text-stone-700">No new private drafts can be created safely from this workbook right now. All existing and held records were preserved.</p>
@endif
@endsection
