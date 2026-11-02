<section id="newsletter" class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-stone-900 via-stone-800 to-amber-950 px-6 py-12 sm:px-12 sm:py-16" aria-labelledby="newsletter-title">
    <div class="pointer-events-none absolute -right-12 -top-12 h-48 w-48 rounded-full bg-amber-600/20 blur-3xl"></div>
    <div class="relative mx-auto max-w-2xl text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-500 text-stone-950" data-lottie-mail aria-hidden="true"><x-icon name="mail" class="h-6 w-6" /></div>
        <h2 id="newsletter-title" class="text-2xl font-bold text-white sm:text-3xl">{{ $region['localizedTerms']['newsletterTitle'] }}</h2>
        <p class="mt-3 text-stone-300">Thoughtful rankings and fresh discoveries from across Africa.</p>
        @if(session('newsletter_status'))
            <p role="status" class="mt-6 rounded-xl bg-green-500/20 px-4 py-4 text-green-200">{{ session('newsletter_status') }}</p>
        @else
        <form action="{{ route('newsletter') }}" method="POST" class="mt-6" data-newsletter-form>
            @csrf
            <input type="hidden" name="region" value="{{ $region['code'] }}">
            <div class="flex flex-col justify-center gap-3 sm:flex-row">
                <div class="w-full sm:max-w-sm"><label for="newsletter-email" class="sr-only">Email address</label><input id="newsletter-email" name="email" type="email" autocomplete="email" maxlength="254" required value="{{ old('email') }}" placeholder="Enter your email" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror class="w-full rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-white placeholder-stone-400 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
                <button type="submit" class="rounded-xl bg-amber-500 px-6 py-3 font-semibold text-stone-950 transition hover:bg-amber-400 disabled:opacity-60">{{ $region['localizedTerms']['subscribe'] }}</button>
            </div>
            <div class="mt-4 flex items-start justify-center gap-2 text-left text-xs text-stone-300"><input type="checkbox" name="consent" id="newsletter-consent" value="1" required @checked(old('consent')) class="mt-0.5 accent-amber-500"><label for="newsletter-consent">I agree to have my email and edition preference stored for the African Ranked newsletter.</label></div>
            <div class="hidden" aria-hidden="true"><label for="newsletter-website">Leave this field empty</label><input id="newsletter-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
            @error('email')<p id="email-error" role="alert" class="mt-3 text-sm text-red-200">{{ $message }}</p>@enderror
            @error('consent')<p role="alert" class="mt-3 text-sm text-red-200">{{ $message }}</p>@enderror
            @error('website')<p role="alert" class="mt-3 text-sm text-red-200">Unable to process this request. Please try again.</p>@enderror
        </form>
        @endif
        <p class="mt-3 text-xs text-stone-400">We use these details for newsletter requests. No confirmation email is sent at this stage.</p>
    </div>
</section>
