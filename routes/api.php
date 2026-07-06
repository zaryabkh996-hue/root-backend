<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
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
use App\Http\Controllers\LoungeController;
use App\Http\Controllers\CommunityReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\KnowledgeBankController;
use App\Http\Controllers\AmenAIController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\StripeController;



// ──────────────────────────────────────────────────────────────────────────
// PUBLIC ROUTES - No authentication required
// ──────────────────────────────────────────────────────────────────────────

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/quiz/submit', [QuizController::class, 'submit']);
Route::post('/quiz/onboarding', [QuizController::class, 'saveOnboardingAnswers']);
Route::get('/quiz/report/{token}', [QuizController::class, 'getReport']);
Route::post('/auth/register-oauth', [AuthController::class, 'registerOAuth']);
Route::post('/auth/magic-link/send', [MagicLinkController::class, 'sendMagicLink']);
Route::post('/auth/magic-link/signin', [MagicLinkController::class, 'signInMagicLink']);
Route::post('/auth/magic-link/verify', [MagicLinkController::class, 'verifyMagicLink']);
Route::post('/auth/admin/login', [AdminAuthController::class, 'login']);

// Public Stripe Webhook (No CSRF/Auth)
Route::post('/stripe/webhook', [StripeController::class, 'handleWebhook']);

// Public custodians routes (no auth required)
Route::get('/custodians', [CustodianController::class, 'getAll']);
Route::get('/custodians/{id}', [CustodianController::class, 'getOne']);
Route::post('/custodians/apply', [CustodianController::class, 'apply']);

// Public stories routes
Route::get('/stories/approved', [ProfileController::class, 'getApprovedStories']);

// ──────────────────────────────────────────────────────────────────────────
// PROTECTED ROUTES - Requires Sanctum token
// ──────────────────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user/quiz-report', [QuizController::class, 'userReport']);
    Route::get('/search', [SearchController::class, 'search']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/save-quiz-data', [AuthController::class, 'saveQuizData']);
    Route::post('/auth/onboarding', [AuthController::class, 'saveOnboarding']);

    // Admin Auth routes
    Route::post('/auth/admin/logout', [AdminAuthController::class, 'logout']);
    Route::get('/auth/admin/me', [AdminAuthController::class, 'me']);

    // Progress routes
    Route::get('/progress', [ProgressController::class, 'show']);
    Route::middleware('tier')->group(function () {
        Route::put('/progress', [ProgressController::class, 'sync']);
        Route::post('/progress/complete-module', [ProgressController::class, 'completeModule']);
        Route::put('/progress/journal/{moduleId}', [ProgressController::class, 'saveJournal']);
        Route::put('/progress/feedback/{moduleId}', [ProgressController::class, 'saveFeedback']);
    });

    // Profile routes
    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::put('/user/notifications', [ProfileController::class, 'updateNotifications']);
    Route::post('/user/profile/picture', [ProfileController::class, 'uploadPicture']);
    Route::post('/user/profile/grant-returned-traveller', [ProfileController::class, 'grantReturnedTraveller']);

    // Stories DB-backed routes
    Route::get('/user/stories', [ProfileController::class, 'listStories']);
    Route::get('/user/stories/{id}', [ProfileController::class, 'showStory']);
    Route::post('/user/stories', [ProfileController::class, 'storeStory']);
    Route::put('/user/stories/{id}', [ProfileController::class, 'updateStory']);

    // Custodian profile routes
    Route::get('/custodian/profile', [CustodianController::class, 'getProfile']);
    Route::put('/custodian/profile', [CustodianController::class, 'updateProfile']);
    
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
    Route::middleware('tier')->group(function () {
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
        Route::post('/community/reports', [CommunityReportController::class, 'store']);
    });

    // Lounge routes
    Route::get('/lounge/posts', [LoungeController::class, 'index']);
    Route::post('/lounge/posts', [LoungeController::class, 'store']);
    Route::post('/lounge/posts/{id}/like', [LoungeController::class, 'toggleLike']);
    Route::post('/lounge/posts/{id}/replies', [LoungeController::class, 'reply']);
    Route::get('/lounge/stats', [LoungeController::class, 'stats']);

    // ──────────────────────────────────────────────────────────────────────────
    // AMEN AI ROUTES - Chat with Amen AI companion
    // ──────────────────────────────────────────────────────────────────────────
    Route::post('/amen-ai/chat', [AmenAIController::class, 'chat']);
    Route::get('/amen-ai/history', [AmenAIController::class, 'history']);
    Route::get('/amen-ai/history/{conversationId}', [AmenAIController::class, 'sessionMessages']);

    // ──────────────────────────────────────────────────────────────────────────
    // KNOWLEDGE BANK ROUTES - Custodian knowledge contributions
    // ──────────────────────────────────────────────────────────────────────────
    Route::post('/knowledge-bank/submit', [KnowledgeBankController::class, 'submit']);
    Route::get('/knowledge-bank/my-contributions', [KnowledgeBankController::class, 'myContributions']);
    Route::get('/knowledge-bank/contributions/{id}', [KnowledgeBankController::class, 'show']);

    // ──────────────────────────────────────────────────────────────────────────
    // ADMIN ROUTES - Requires Sanctum token + admin role (consider adding middleware)
    // ──────────────────────────────────────────────────────────────────────────
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'getUsers']);
        Route::get('/stats', [AdminUserController::class, 'getStats']);
        Route::get('/custodians', [CustodianController::class, 'getForAdmin']);
        Route::get('/custodians/{id}', [CustodianController::class, 'show']);
        Route::post('/custodians', [CustodianController::class, 'store']);
        Route::put('/custodians/{id}', [CustodianController::class, 'update']);
        Route::delete('/custodians/{id}', [CustodianController::class, 'destroy']);

        // Community admin routes
        Route::post('/community/hubs', [CommunityHubController::class, 'store']);
        Route::put('/community/hubs/{id}', [CommunityHubController::class, 'update']);
        Route::get('/community/threads/pending', [CommunityThreadController::class, 'getPendingThreads']);
        Route::post('/community/threads/{id}/approve', [CommunityThreadController::class, 'approveThread']);
        Route::post('/community/threads/{id}/revision', [CommunityThreadController::class, 'requestThreadRevision']);

        // Stories admin review routes
        Route::get('/stories/pending', [ProfileController::class, 'getPendingStories']);
        Route::post('/stories/{id}/approve', [ProfileController::class, 'approveStory']);
        Route::post('/stories/{id}/revision', [ProfileController::class, 'rejectStory']);

        // Conduct & reports routes
        Route::get('/community/reports', [CommunityReportController::class, 'index']);
        Route::post('/community/reports/{id}/warn', [CommunityReportController::class, 'warn']);
        Route::post('/community/reports/{id}/ban', [CommunityReportController::class, 'ban']);
        Route::post('/community/reports/{id}/dismiss', [CommunityReportController::class, 'dismiss']);

        // Knowledge Bank admin routes
        Route::get('/knowledge-bank/contributions', [KnowledgeBankController::class, 'adminIndex']);
        Route::put('/knowledge-bank/contributions/{id}/status', [KnowledgeBankController::class, 'updateStatus']);
        Route::post('/knowledge-bank/embed/{id}', [KnowledgeBankController::class, 'embed']);
    });

    // Stripe billing and checkout routes
    Route::post('/stripe/checkout', [StripeController::class, 'createCheckoutSession']);
    Route::post('/stripe/portal', [StripeController::class, 'createPortalSession']);
    
});

