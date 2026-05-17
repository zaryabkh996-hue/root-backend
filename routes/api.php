<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\MagicLinkController;
use App\Http\Controllers\ProgressController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/register-oauth', [AuthController::class, 'registerOAuth']);

// Magic Link Registration Routes
Route::post('/auth/magic-link/send', [MagicLinkController::class, 'sendMagicLink']);
Route::post('/auth/magic-link/signin', [MagicLinkController::class, 'signInMagicLink']);
Route::post('/auth/magic-link/verify', [MagicLinkController::class, 'verifyMagicLink']);

// Public Library Routes (Customer View)
Route::get('/libraries', [LibraryController::class, 'index']);
Route::get('/libraries/{id}', [LibraryController::class, 'show']);

// ── Protected routes (Sanctum token required) ──────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/save-quiz-data', [AuthController::class, 'saveQuizData']);

    // Progress sync routes
    Route::get('/progress', [ProgressController::class, 'show']);
    Route::put('/progress', [ProgressController::class, 'sync']);
    Route::post('/progress/complete-module', [ProgressController::class, 'completeModule']);
    Route::put('/progress/journal/{moduleId}', [ProgressController::class, 'saveJournal']);
    Route::put('/progress/feedback/{moduleId}', [ProgressController::class, 'saveFeedback']);

    // Admin CRUD operations
    Route::post('/libraries', [LibraryController::class, 'store']);
    Route::put('/libraries/{id}', [LibraryController::class, 'update']);
    Route::delete('/libraries/{id}', [LibraryController::class, 'destroy']);
    Route::get('/admin/libraries', [LibraryController::class, 'adminList']);
});

