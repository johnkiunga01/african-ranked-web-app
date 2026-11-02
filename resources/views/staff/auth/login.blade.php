@extends('staff.layout')
@section('title', 'Staff sign in')
@section('content')
<section class="staff-panel mx-auto mt-6 max-w-md"><p class="text-xs font-semibold uppercase tracking-widest text-amber-700">Welcome back</p><h1 class="mt-2 text-3xl font-bold">Sign in to your workspace</h1><p class="mt-3 text-sm leading-relaxed text-stone-600">For invited African Ranked authors and administrators.</p>
<form method="POST" action="{{ route('staff.login.submit') }}" class="mt-7 space-y-5">@csrf<div><label class="staff-label" for="email">Email address</label><input class="staff-input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div><div><label class="staff-label" for="password">Password</label><input class="staff-input" id="password" name="password" type="password" required autocomplete="current-password"></div><button type="submit" class="staff-button w-full">Sign in</button></form>
<a href="{{ route('staff.password.request') }}" class="mt-5 inline-block text-sm font-medium text-amber-800">Forgot your password?</a><p class="mt-6 border-t border-stone-100 pt-4 text-xs text-stone-500">Accounts are invitation-only. Ask your administrator for access.</p></section>
@endsection
