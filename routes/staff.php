<?php

use App\Http\Controllers\Staff\ArticleController;
use App\Http\Controllers\Staff\ArticleCurationController;
use App\Http\Controllers\Staff\ArticleOperationsController;
use App\Http\Controllers\Staff\ArticleToolsController;
use App\Http\Controllers\Staff\AuthController;
use App\Http\Controllers\Staff\BulkArticleController;
use App\Http\Controllers\Staff\ContentReadinessController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\GrowthController;
use App\Http\Controllers\Staff\PromotionController;
use App\Http\Controllers\Staff\ProviderCredentialController;
use App\Http\Controllers\Staff\SearchIntegrationController;
use App\Http\Controllers\Staff\SecurityController;
use App\Http\Controllers\Staff\SeoFixController;
use App\Http\Controllers\Staff\SitePageController;
use App\Http\Controllers\Staff\UserController;
use App\Http\Controllers\Staff\WebsiteController;
use App\Services\LaunchReadiness;
use Illuminate\Support\Facades\Route;

Route::prefix('staff')->name('staff.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('two-factor-challenge', [SecurityController::class, 'challengeForm'])->name('two-factor.challenge');
        Route::post('two-factor-challenge', [SecurityController::class, 'challenge'])->middleware('throttle:staff-two-factor')->name('two-factor.verify');
        Route::get('login', [AuthController::class, 'loginForm'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:staff-login')->name('login.submit');
        Route::get('forgot-password', [AuthController::class, 'forgotForm'])->name('password.request');
        Route::post('forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:3,1')->name('password.email');
        Route::get('reset-password/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
        Route::post('reset-password', [AuthController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
        Route::get('invitations/{token}', [AuthController::class, 'invitation'])->name('invitation');
        Route::post('invitations/{token}', [AuthController::class, 'accept'])->middleware('throttle:6,1')->name('invitation.accept');
    });
    Route::middleware(['auth', 'staff'])->group(function () {
        Route::middleware('can:manage-staff')->group(function () {
            Route::get('website/homepage', [WebsiteController::class, 'homepage'])->name('website.homepage');
            Route::put('website/homepage', [WebsiteController::class, 'saveHomepage'])->name('website.homepage.save');
            Route::post('website/featured/{article}', [WebsiteController::class, 'feature'])->name('website.feature');
            Route::get('website/settings', [WebsiteController::class, 'general'])->middleware('password.confirm')->name('website.settings');
            Route::put('website/settings', [WebsiteController::class, 'saveGeneral'])->middleware('password.confirm')->name('website.settings.save');
            Route::get('website/regions', [WebsiteController::class, 'regions'])->name('website.regions');
            Route::get('website/regions/create', [WebsiteController::class, 'regionForm'])->name('website.regions.create');
            Route::get('website/regions/{region}/edit', [WebsiteController::class, 'regionForm'])->name('website.regions.edit');
            Route::post('website/regions', [WebsiteController::class, 'saveRegion'])->name('website.regions.store');
            Route::put('website/regions/{region}', [WebsiteController::class, 'saveRegion'])->name('website.regions.update');
            Route::get('pages', [SitePageController::class, 'index'])->name('pages.index');
            Route::get('pages/create', [SitePageController::class, 'edit'])->name('pages.create');
            Route::post('pages', [SitePageController::class, 'save'])->name('pages.store');
            Route::get('pages/{page}/edit', [SitePageController::class, 'edit'])->name('pages.edit');
            Route::put('pages/{page}', [SitePageController::class, 'save'])->name('pages.update');
            Route::get('pages/{page}/preview', [SitePageController::class, 'preview'])->name('pages.preview');
            Route::post('pages/{page}/{action}', [SitePageController::class, 'action'])->name('pages.action');
        });
        Route::put('article-quick-edit/{article}', [ArticleOperationsController::class, 'quickEdit'])->name('articles.quick-edit');
        Route::post('article-lifecycle/{article}/{action}', [ArticleOperationsController::class, 'lifecycle'])->name('articles.lifecycle');
        Route::get('articles/{article}/publish', [ArticleOperationsController::class, 'publishForm'])->name('articles.publish-form');
        Route::post('articles/{article}/publish-reviewed', [ArticleOperationsController::class, 'publish'])->name('articles.publish-reviewed');

        Route::middleware(['password.confirm', 'throttle:10,1'])->group(function () {
            Route::get('security', [SecurityController::class, 'index'])->name('security.index');
            Route::post('security/two-factor', [SecurityController::class, 'begin'])->name('security.begin');
            Route::post('security/two-factor/confirm', [SecurityController::class, 'confirm'])->name('security.confirm');
            Route::post('security/recovery-codes', [SecurityController::class, 'recovery'])->name('security.recovery');
            Route::delete('security/two-factor', [SecurityController::class, 'disable'])->name('security.disable');
            Route::delete('security/two-factor/pending', [SecurityController::class, 'cancelSetup'])->name('security.cancel');
        });
        Route::get('content-readiness', ContentReadinessController::class)->name('content-readiness');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('search-reports', [SearchIntegrationController::class, 'import'])->middleware(['can:manage-staff', 'password.confirm', 'throttle:6,1'])->name('search.import');
        Route::post('openseo/check', [SearchIntegrationController::class, 'check'])->middleware(['can:manage-staff', 'password.confirm', 'throttle:3,1'])->name('openseo.check');
        Route::get('launch-readiness', function (LaunchReadiness $readiness) {
            return view('staff.launch-readiness', ['report' => $readiness->report()]);
        })->middleware('can:manage-staff')->name('launch-readiness');
        Route::get('growth/{section}', [GrowthController::class, 'index'])->middleware('can:manage-staff')->name('growth');
        Route::put('growth-plans/{provider}', [GrowthController::class, 'update'])->middleware(['can:manage-staff', 'password.confirm'])->name('growth.update');
        Route::put('provider-credentials/{provider}', [ProviderCredentialController::class, 'update'])->middleware(['can:manage-staff', 'password.confirm', 'throttle:5,1'])->name('credentials.update');
        Route::delete('provider-credentials/{provider}', [ProviderCredentialController::class, 'destroy'])->middleware(['can:manage-staff', 'password.confirm', 'throttle:5,1'])->name('credentials.destroy');
        Route::post('articles/{article}/curate', [ArticleCurationController::class, 'generate'])->middleware(['can:curate,article', 'password.confirm', 'throttle:2,1'])->name('articles.curate');
        Route::post('article-batches', [ArticleToolsController::class, 'bulk'])->middleware('throttle:10,1')->name('articles.bulk');
        Route::post('article-batches/execute', [BulkArticleController::class, 'execute'])->middleware('throttle:6,1')->name('articles.bulk.execute');
        Route::get('seo-batches/{batch}', [BulkArticleController::class, 'show'])->name('articles.seo-batches.show');
        Route::post('articles/{article}/seo-fix', [SeoFixController::class, 'fix'])->middleware('throttle:2,1')->name('articles.seo-fix');
        Route::get('articles/{article}/promotion', [PromotionController::class, 'show'])->name('articles.promotion');
        Route::post('articles/{article}/promotion', [PromotionController::class, 'generate'])->middleware('throttle:2,1')->name('articles.promotion.generate');
        Route::get('articles/{article}/seo', [ArticleToolsController::class, 'seo'])->name('articles.seo');
        Route::put('articles/{article}/seo-metadata', [ArticleToolsController::class, 'metadata'])->name('articles.seo-metadata');
        Route::get('articles/{article}/revisions/{revision}', [ArticleToolsController::class, 'revision'])->name('articles.revision');
        Route::post('articles/{article}/revisions/{revision}/restore', [ArticleToolsController::class, 'restore'])->name('articles.restore');
        Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
        Route::post('article-imports/{slug}', [ArticleController::class, 'import'])->middleware('can:manage-staff')->name('articles.import');
        Route::patch('article-assignments/{article}', [ArticleController::class, 'assign'])->middleware('can:manage-staff')->name('articles.assign');
        Route::get('articles/create', [ArticleController::class, 'create'])->name('articles.create');
        Route::post('articles', [ArticleController::class, 'store'])->name('articles.store');
        Route::get('articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
        Route::put('articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
        Route::get('articles/{article}/preview', [ArticleController::class, 'preview'])->name('articles.preview');
        Route::post('articles/{article}/{action}', [ArticleController::class, 'action'])->name('articles.action');
        Route::middleware(['can:manage-staff', 'password.confirm'])->group(function () {
            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::post('invitations', [UserController::class, 'invite'])->name('users.invite');
            Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('invitations/{invitation}', [UserController::class, 'revoke'])->name('invitations.revoke');
        });
    });
});
Route::get('/staff/confirm-password', [AuthController::class, 'confirmForm'])->middleware(['auth', 'staff'])->name('password.confirm');
Route::post('/staff/confirm-password', [AuthController::class, 'confirm'])->middleware(['auth', 'staff', 'throttle:6,1'])->name('password.confirm.submit');
