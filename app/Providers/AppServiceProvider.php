<?php

namespace App\Providers;

use App\Models\ArticleRevision;
use App\Models\User;
use App\Services\OnsiteSeoDefaults;
use App\Support\Catalog;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Catalog::class);
        Passport::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::loadKeysFrom(storage_path('app/private/oauth'));
        Passport::tokensCan(['editorial' => 'Read unpublished drafts, save private revisions and submit for human review. No approval or publication.']);
        Passport::tokensExpireIn(now()->addHour());
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::authorizationView('staff.oauth-consent');
        ArticleRevision::creating(fn ($revision) => app(OnsiteSeoDefaults::class)->apply($revision));
        Gate::define('manage-staff', fn (User $user) => $user->isSuperAdmin());
        RateLimiter::for('staff-two-factor', fn (Request $request) => [
            Limit::perMinute(5)->by('mfa-account:'.($request->session()->get('staff_pending_mfa.id') ?? 'guest').'|'.$request->ip()),
            Limit::perMinute(20)->by('mfa-ip:'.$request->ip()),
        ]);
        RateLimiter::for('staff-login', fn (Request $request) => Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
        ResetPassword::createUrlUsing(fn (User $user, string $token) => route('staff.password.reset', ['token' => $token, 'email' => $user->email]));

        if (config('review.enabled')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme(parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'http');
        }
    }
}
