<?php

use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\EditorialCoverArtController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NewsletterLifecycleController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Staff\ArticleMediaController;
use Illuminate\Support\Facades\Route;

Route::get('/editorial-art/{slug}/{variant}.svg', [EditorialCoverArtController::class, 'show'])->where(['slug' => '[a-z0-9-]+', 'variant' => '(?:cover|detail-[1-9][0-9]?)'])->name('editorial-art.show');
Route::get('/sitemap.xml', [DiscoveryController::class, 'sitemap'])->name('sitemap');
Route::get('/sitemaps/{region}.xml', [DiscoveryController::class, 'sitemap']);
Route::get('/robots.txt', [DiscoveryController::class, 'robots']);
Route::get('/editorial-media/{filename}', [ArticleMediaController::class, 'show'])->where('filename', '[a-f0-9-]{36}\\.webp')->name('editorial-media.show');
Route::get('/feed.xml', [DiscoveryController::class, 'feed'])->name('feed');
Route::get('/llms.txt', [DiscoveryController::class, 'llms']);
Route::post('/newsletter', NewsletterController::class)->middleware('throttle:5,1')->name('newsletter');
Route::get('/newsletter/confirm/{subscriber}/{token}', [NewsletterLifecycleController::class, 'confirmForm'])
    ->where(['subscriber' => '[0-9]+', 'token' => '[a-f0-9]{64}'])
    ->middleware('throttle:10,1')->name('newsletter.confirm');
Route::post('/newsletter/confirm/{subscriber}/{token}', [NewsletterLifecycleController::class, 'confirm'])
    ->where(['subscriber' => '[0-9]+', 'token' => '[a-f0-9]{64}'])
    ->middleware('throttle:10,1')->name('newsletter.confirm.submit');
Route::get('/newsletter/unsubscribe/{subscriber}', [NewsletterLifecycleController::class, 'unsubscribeForm'])
    ->whereNumber('subscriber')->middleware(['signed', 'throttle:10,1'])->name('newsletter.unsubscribe');
Route::post('/newsletter/unsubscribe/{subscriber}', [NewsletterLifecycleController::class, 'unsubscribe'])
    ->whereNumber('subscriber')->middleware(['signed', 'throttle:10,1'])->name('newsletter.unsubscribe.submit');

Route::get('/global/{path?}', [PageController::class, 'legacyGlobal'])->where('path', '.*');

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/rankings', [PageController::class, 'rankings'])->name('rankings');
Route::get('/categories/{slug}', [PageController::class, 'category'])->name('category');
Route::get('/articles/{slug}', [PageController::class, 'article'])->name('article');
Route::get('/pages/{slug}', [ContentPageController::class, 'show'])->name('content-page');
Route::get('/strategy', [PageController::class, 'strategy'])->name('strategy');
Route::prefix('{region}')->where(['region' => '[a-z]{2}'])->name('regional.')->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/rankings', [PageController::class, 'rankings'])->name('rankings');
    Route::get('/categories/{slug}', [PageController::class, 'regionalCategory'])->name('category');
    Route::get('/articles/{slug}', [PageController::class, 'regionalArticle'])->name('article');
    Route::get('/strategy', [PageController::class, 'strategy'])->name('strategy');
});

require __DIR__.'/staff.php';

require __DIR__.'/editorial-oauth.php';
