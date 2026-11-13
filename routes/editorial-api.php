<?php

use App\Http\Controllers\EditorialIntegrationController;
use App\Http\Controllers\EditorialMcpController;
use App\Http\Controllers\NewsletterOperatorTestController;
use App\Http\Controllers\PolicyDraftIntegrationController;
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

// Scoped policy-draft intake; deliberately separate from the OAuth editorial MCP tool scope.
Route::prefix('policies/v1')->middleware([EditorialApiAccess::class, 'throttle:20,1'])->group(function () {
    Route::get('pages', [PolicyDraftIntegrationController::class, 'index']);
    Route::get('pages/{page}', [PolicyDraftIntegrationController::class, 'show'])->whereNumber('page');
    Route::post('pages/{slug}/draft', [PolicyDraftIntegrationController::class, 'save']);
    Route::get('pages/{page}/preview', [PolicyDraftIntegrationController::class, 'preview'])->whereNumber('page');
});

Route::prefix('launch-tests/v1')->middleware([EditorialApiAccess::class, 'throttle:10,1'])->group(function () {
    Route::post('newsletter', [NewsletterOperatorTestController::class, 'start']);
    Route::get('newsletter/{subscriber}', [NewsletterOperatorTestController::class, 'state'])->whereNumber('subscriber');
});
