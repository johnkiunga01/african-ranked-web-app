<?php

use App\Http\Controllers\EditorialIntegrationController;
use App\Http\Middleware\EditorialApiAccess;
use Illuminate\Support\Facades\Route;

Route::prefix('editorial/v1')->middleware([EditorialApiAccess::class, 'throttle:30,1'])->group(function () {
    Route::get('drafts', [EditorialIntegrationController::class, 'drafts']);
    Route::get('articles/{article}', [EditorialIntegrationController::class, 'show']);
    Route::put('articles/{article}/draft', [EditorialIntegrationController::class, 'save']);
    Route::post('articles/{article}/submit', [EditorialIntegrationController::class, 'submit']);
});
