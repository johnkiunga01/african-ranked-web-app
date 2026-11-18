<?php

use App\Http\Middleware\EditorialMcpEnabled;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;

Route::get('/.well-known/oauth-protected-resource', function () {
    abort_unless(config('editorial.mcp_enabled'), 404);

    return response()->json(['resource' => url('/api/editorial/mcp'), 'authorization_servers' => [url('/')], 'scopes_supported' => ['editorial'], 'bearer_methods_supported' => ['header']]);
});
Route::get('/.well-known/oauth-authorization-server', function () {
    abort_unless(config('editorial.mcp_enabled'), 404);

    return response()->json(['issuer' => url('/'), 'authorization_response_iss_parameter_supported' => true, 'authorization_endpoint' => url('/oauth/authorize'), 'token_endpoint' => url('/api/editorial/oauth/token'), 'response_types_supported' => ['code'], 'grant_types_supported' => ['authorization_code', 'refresh_token'], 'code_challenge_methods_supported' => ['S256'], 'token_endpoint_auth_methods_supported' => ['client_secret_post', 'none'], 'scopes_supported' => ['editorial']]);
});
Route::middleware([EditorialMcpEnabled::class, 'auth', 'staff', 'can:manage-staff', 'throttle:20,1'])->group(function () {
    Route::get('/oauth/authorize', [AuthorizationController::class, 'authorize'])->name('passport.authorizations.authorize');
    Route::post('/oauth/authorize', [ApproveAuthorizationController::class, 'approve'])->name('passport.authorizations.approve');
    Route::delete('/oauth/authorize', [DenyAuthorizationController::class, 'deny'])->name('passport.authorizations.deny');
});
