<?php

use App\Http\Controllers\AgentDraftController;
use App\Http\Controllers\PublicContentController;
use App\Http\Middleware\AgentTokenOnly;
use Illuminate\Support\Facades\Route;

// Token needs the "drafts:create" ability. Create it with:
// $user->createToken('editorial-agent', ['drafts:create'])->plainTextToken
Route::middleware(['auth:sanctum', AgentTokenOnly::class, 'abilities:drafts:create', 'throttle:30,1'])
    ->post('/agent/drafts', AgentDraftController::class);

Route::get('/content/articles', PublicContentController::class);
