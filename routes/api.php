<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\MagicLinkController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\CustodianController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CommunityHubController;
use App\Http\Controllers\CommunityThreadController;
use App\Http\Controllers\CommunityReplyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CertificateController;



// ──────────────────────────────────────────────────────────────────────────
// PUBLIC ROUTES - No authentication required
// ──────────────────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/register-oauth', [AuthController::class, 'registerOAuth']);
Route::post('/auth/magic-link/send', [MagicLinkController::class, 'sendMagicLink']);
Route::post('/auth/magic-link/signin', [MagicLinkController::class, 'signInMagicLink']);
Route::post('/auth/magic-link/verify', [MagicLinkController::class, 'verifyMagicLink']);
Route::post('/auth/admin/login', [AdminAuthController::class, 'login']);

// ──────────────────────────────────────────────────────────────────────────
// PROTECTED ROUTES - Requires Sanctum token
// ──────────────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/save-quiz-data', [AuthController::class, 'saveQuizData']);

    // Admin Auth routes
    Route::post('/auth/admin/logout', [AdminAuthController::class, 'logout']);
    Route::get('/auth/admin/me', [AdminAuthController::class, 'me']);

    // Public API routes (accessible by authenticated users)
    Route::get('/custodians', [CustodianController::class, 'getAll']);
    Route::get('/custodians/{id}', [CustodianController::class, 'getOne']);

    // Progress routes
    Route::get('/progress', [ProgressController::class, 'show']);
    Route::put('/progress', [ProgressController::class, 'sync']);
    Route::post('/progress/complete-module', [ProgressController::class, 'completeModule']);
    Route::put('/progress/journal/{moduleId}', [ProgressController::class, 'saveJournal']);
    Route::put('/progress/feedback/{moduleId}', [ProgressController::class, 'saveFeedback']);

    // Profile routes
    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::put('/user/notifications', [ProfileController::class, 'updateNotifications']);
    Route::post('/user/profile/picture', [ProfileController::class, 'uploadPicture']);
    
    // Journey photos routes
    Route::get('/user/journey-photos', [ProfileController::class, 'getJourneyPhotos']);
    Route::post('/user/journey-photos', [ProfileController::class, 'uploadJourneyPhoto']);

    // Certificate routes
    Route::get('/certificates/info', [CertificateController::class, 'getInfo']);
    Route::get('/certificates', [CertificateController::class, 'listCertificates']);
    Route::get('/certificates/download', [CertificateController::class, 'download']);

    // Booking routes
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/my-bookings', [BookingController::class, 'getUserBookings']);
    Route::get('/bookings/custodian-calendar', [BookingController::class, 'getCustodianBookings']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    // Community routes
    Route::get('/community/hubs', [CommunityHubController::class, 'index']);
    Route::get('/community/hubs/{id}', [CommunityHubController::class, 'show']);
    Route::post('/community/hubs/{id}/join', [CommunityHubController::class, 'join']);
    Route::post('/community/hubs/{id}/leave', [CommunityHubController::class, 'leave']);
    
    Route::get('/community/hubs/{hubId}/threads', [CommunityThreadController::class, 'indexByHub']);
    Route::post('/community/threads', [CommunityThreadController::class, 'store']);
    Route::get('/community/threads/{id}', [CommunityThreadController::class, 'show']);
    Route::put('/community/threads/{id}', [CommunityThreadController::class, 'update']);
    Route::delete('/community/threads/{id}', [CommunityThreadController::class, 'destroy']);
    
    Route::get('/community/threads/{threadId}/replies', [CommunityReplyController::class, 'indexByThread']);
    Route::post('/community/replies', [CommunityReplyController::class, 'store']);
    Route::put('/community/replies/{id}', [CommunityReplyController::class, 'update']);
    Route::delete('/community/replies/{id}', [CommunityReplyController::class, 'destroy']);

    // Library routes
    Route::post('/libraries', [LibraryController::class, 'store']);
    Route::put('/libraries/{id}', [LibraryController::class, 'update']);
    Route::delete('/libraries/{id}', [LibraryController::class, 'destroy']);

    // ──────────────────────────────────────────────────────────────────────────
    // ADMIN ROUTES - Requires Sanctum token + admin role (consider adding middleware)
    // ──────────────────────────────────────────────────────────────────────────
    Route::prefix('admin')->group(function () {
        Route::get('/libraries', [LibraryController::class, 'adminList']);
        Route::get('/users', [AdminUserController::class, 'getUsers']);
        Route::get('/custodians', [CustodianController::class, 'getForAdmin']);
        Route::post('/custodians', [CustodianController::class, 'store']);
        Route::put('/custodians/{id}', [CustodianController::class, 'update']);
        Route::delete('/custodians/{id}', [CustodianController::class, 'destroy']);

        // Community admin routes
        Route::post('/community/hubs', [CommunityHubController::class, 'store']);
        Route::put('/community/hubs/{id}', [CommunityHubController::class, 'update']);
    });
});

