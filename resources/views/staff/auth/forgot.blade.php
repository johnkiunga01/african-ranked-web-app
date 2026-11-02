@extends('staff.layout')
@section('title', 'Reset your password')
@section('content')
<section class="staff-panel mx-auto max-w-md"><h1 class="text-2xl font-bold">Reset your password</h1><p class="mt-3 text-sm text-stone-600">Enter the email address associated with your staff account.</p><form action="{{ route('staff.password.email') }}" method="POST" class="mt-6 space-y-5">@csrf<div><label class="staff-label" for="email">Email address</label><input class="staff-input" id="email" name="email" type="email" required autocomplete="email" value="{{ old('email') }}"></div><button class="staff-button w-full" type="submit">Request password reset</button></form><a class="mt-5 inline-block text-sm text-amber-800" href="{{ route('staff.login') }}">Back to sign in</a></section>
@endsection
