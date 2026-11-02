<?php

use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [DiscoveryController::class, 'sitemap'])->name('sitemap');
Route::get('/sitemaps/{region}.xml', [DiscoveryController::class, 'sitemap']);
Route::get('/robots.txt', [DiscoveryController::class, 'robots']);
Route::get('/feed.xml', [DiscoveryController::class, 'feed'])->name('feed');
Route::get('/llms.txt', [DiscoveryController::class, 'llms']);
Route::post('/newsletter', NewsletterController::class)->middleware('throttle:5,1')->name('newsletter');

Route::get('/global/{path?}', [PageController::class, 'legacyGlobal'])->where('path', '.*');

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/categories/{slug}', [PageController::class, 'category'])->name('category');
Route::get('/articles/{slug}', [PageController::class, 'article'])->name('article');
Route::get('/pages/{slug}', [ContentPageController::class, 'show'])->name('content-page');
Route::get('/strategy', [PageController::class, 'strategy'])->name('strategy');
Route::prefix('{region}')->where(['region' => '[a-z]{2}'])->name('regional.')->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/categories/{slug}', [PageController::class, 'regionalCategory'])->name('category');
    Route::get('/articles/{slug}', [PageController::class, 'regionalArticle'])->name('article');
    Route::get('/strategy', [PageController::class, 'strategy'])->name('strategy');
});

require __DIR__.'/staff.php';
