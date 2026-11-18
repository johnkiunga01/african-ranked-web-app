<?php

use App\Http\Middleware\PreviewIndexing;
use App\Http\Middleware\ProductionHost;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\StaffAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/editorial-api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(ProductionHost::class);
        $middleware->append(PreviewIndexing::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->alias(['staff' => StaffAccess::class]);
        $middleware->redirectGuestsTo(fn () => route('staff.login'));
        $middleware->redirectUsersTo(fn () => route('staff.dashboard'));

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['secret', 'api_key', 'password', 'password_confirmation', 'current_password', 'code', 'recovery_codes']);
        $exceptions->respond(function (Response $response) {
            if ($response->getStatusCode() === 419 && request()->is('staff/login') && request()->isMethod('POST')) {
                return redirect()->route('staff.login', [], 303)
                    ->with('status', 'Your sign-in form expired. Please enter your email and password again using this refreshed form.')
                    ->header('Cache-Control', 'no-store, private')
                    ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
            }

            if ($response->getStatusCode() === 419 && request()->is('staff', 'staff/*')) {
                return redirect()->route('staff.login', [], 303)
                    ->with('status', 'Your form or session expired. Open a fresh page before resubmitting. Submitted values were not replayed.')
                    ->header('Cache-Control', 'no-store, private')
                    ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
            }

            return $response;
        });
    })->create();
