<?php

use App\Http\Controllers\EditorialIntegrationController;
use App\Http\Controllers\EditorialMcpController;
use App\Http\Middleware\EditorialApiAccess;
use App\Http\Middleware\EditorialMcpAccess;
use App\Http\Middleware\EditorialMcpEnabled;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\AccessTokenController;

Route::prefix('editorial/v1')->middleware([EditorialApiAccess::class, 'throttle:30,1'])->group(function () {
    Route::get('drafts', [EditorialIntegrationController::class, 'drafts']);
    Route::get('articles/{article}', [EditorialIntegrationController::class, 'show']);
    Route::put('articles/{article}/draft', [EditorialIntegrationController::class, 'save']);
    Route::post('articles/{article}/submit', [EditorialIntegrationController::class, 'submit']);
});

// MCP discovery/read transport shares the existing bearer gate; OAuth onboarding follows separately.
Route::post('editorial/mcp', EditorialMcpController::class)
    ->middleware([EditorialMcpAccess::class, 'throttle:30,1']);

Route::post('editorial/oauth/token', [AccessTokenController::class, 'issueToken'])->middleware([EditorialMcpEnabled::class, 'throttle:20,1']);
