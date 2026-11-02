<?php

use App\Http\Controllers\FrontendController;
use App\Http\Controllers\LlmsTxtController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', RobotsController::class);
Route::get('/llms.txt', LlmsTxtController::class);
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemaps/{edition}-{page}.xml', [SitemapController::class, 'show'])
    ->where(['edition' => '[a-z]{2,6}', 'page' => '[1-9][0-9]*']);
// Keep the site's original static sitemap URLs working under the backend too.
Route::get('/sitemaps/{edition}.xml', fn (string $edition) => app(SitemapController::class)->show($edition, 1))
    ->where('edition', 'global|us|uk|au|ca|ng|za|ke');
Route::get('/assets/{file}', function (string $file) {
    abort_unless(basename($file) === $file, 404);
    $path = config('cms.frontend_dist').'/assets/'.$file;
    abort_unless(is_file($path), 404);
    $type = match (pathinfo($file, PATHINFO_EXTENSION)) {
        'js' => 'application/javascript', 'css' => 'text/css', default => 'application/octet-stream',
    };

    return response()->file($path, ['Content-Type' => $type]);
})->where('file', '[A-Za-z0-9_.-]+');
Route::get('/', FrontendController::class);
Route::get('/{edition}', FrontendController::class)->where('edition', 'global|us|uk|au|ca|ng|za|ke');
foreach (['articles/{slug}', 'categories/{slug}', 'strategy'] as $path) {
    Route::get('/'.$path, FrontendController::class);
    Route::get('/{edition}/'.$path, FrontendController::class)->where('edition', 'global|us|uk|au|ca|ng|za|ke');
}
Route::get('/authors/{slug}', FrontendController::class);
Route::get('/e/{slug}', FrontendController::class);
Route::get('/corrections', FrontendController::class);
Route::get('/{page}', FrontendController::class)->where('page', 'about|methodology|editorial-policy|monetization-disclosure');
