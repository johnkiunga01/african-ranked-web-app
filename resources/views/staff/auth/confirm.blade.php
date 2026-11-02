@extends('staff.layout')
@section('title', 'Confirm your password')
@section('content')
<section class="staff-panel mx-auto max-w-md"><h1 class="text-2xl font-bold">Confirm your password</h1><p class="mt-3 text-sm text-stone-600">Please confirm your password to manage team access.</p><form method="POST" action="{{ route('password.confirm.submit') }}" class="mt-6 space-y-5">@csrf<label for="password" class="staff-label">Current password</label><input id="password" class="staff-input" name="password" type="password" required autocomplete="current-password"><button class="staff-button" type="submit">Continue</button></form></section>
@endsection
