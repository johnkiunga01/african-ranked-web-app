@extends('staff.layout')
@section('title', 'My profile')
@section('content')
<h1 class="text-3xl font-bold">My profile</h1>
<p class="mt-2 text-sm text-stone-600">Manage your account details. Profile changes apply to future article revisions.</p>
<div class="mt-7 grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(260px,1fr)]">
<section class="staff-panel">
<h2 class="text-xl font-bold">Profile details</h2>
<form method="POST" action="{{ route('staff.profile.update') }}" class="mt-5 space-y-5">
@csrf @method('PUT')
<input type="hidden" name="updated_at" value="{{ $member->updated_at->toISOString() }}">
<div><label for="profile-name" class="staff-label">Display name</label><input id="profile-name" class="staff-input" name="name" autocomplete="name" value="{{ old('name', $member->name) }}" required maxlength="100"><p class="mt-2 text-xs text-stone-500">Use the author or editorial identity you are accountable for. A brand name does not represent independent human review.</p></div>
<div><label for="profile-title" class="staff-label">Editorial title</label><input id="profile-title" class="staff-input" name="job_title" value="{{ old('job_title', $member->job_title) }}" required maxlength="100"></div>
<div><label for="profile-bio" class="staff-label">Biography</label><textarea id="profile-bio" class="staff-input" name="bio" rows="5" maxlength="2000">{{ old('bio', $member->bio) }}</textarea><p class="mt-2 text-xs text-stone-500">Describe actual experience. Do not add credentials or verification that you cannot support.</p></div>
<button type="submit" class="staff-button">Save profile</button>
</form>
</section>
<aside class="space-y-5">
<section class="staff-panel"><h2 class="text-lg font-bold">Sign-in details</h2><p class="mt-3 break-all text-sm">{{ $member->email }}</p><p class="mt-2 text-xs text-stone-500">Your verified email remains your sign-in identity. Email changes require a verified transfer and are not available on this page.</p><a class="staff-secondary mt-4" href="{{ route('staff.password.request') }}">Reset password by email</a></section>
<section class="staff-panel"><h2 class="text-lg font-bold">Account security</h2><p class="mt-2 text-sm text-stone-600">Manage your authenticator and recovery codes.</p><a class="staff-secondary mt-4" href="{{ route('staff.security.index') }}">Open account security</a></section>
</aside>
</div>
@endsection
