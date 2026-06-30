<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionTier
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            \Log::warning('🛡️ [TIER_MIDDLEWARE] Unauthorized request (no authenticated user)');
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 401);
        }

        $userTier = $user->subscription_tier ?? 'free';

        \Log::info('🛡️ [TIER_MIDDLEWARE] Request received', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => $user->id,
            'user_tier' => $userTier,
            'role' => $user->role
        ]);

        // Admins bypass all tier checks
        if ($user->role === 'admin') {
            \Log::info('🛡️ [TIER_MIDDLEWARE] Admin bypass granted');
            return $next($request);
        }

        // 1. Check Progress / Module Routes
        // Routes like complete-module, journal, feedback have module_id in body or route params
        $moduleId = $request->route('moduleId') ?? $request->input('module_id');
        if ($moduleId) {
            $stageId = (int) explode('.', $moduleId)[0];
            \Log::info('🛡️ [TIER_MIDDLEWARE] Checking progress update access', [
                'module_id' => $moduleId,
                'stage_id' => $stageId,
                'user_tier' => $userTier
            ]);
            if (!$this->validateStageAccess($userTier, $stageId)) {
                \Log::warning('🛡️ [TIER_MIDDLEWARE] Access denied for progress update', [
                    'user_tier' => $userTier,
                    'stage_id' => $stageId,
                    'module_id' => $moduleId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => "Your subscription tier does not allow access to Stage {$stageId}."
                ], 403);
            }
        }

        // For progress sync payload
        if ($request->has('current_module_id')) {
            $stageId = (int) explode('.', $request->current_module_id)[0];
            \Log::info('🛡️ [TIER_MIDDLEWARE] Checking progress sync current_module_id', [
                'current_module_id' => $request->current_module_id,
                'stage_id' => $stageId,
                'user_tier' => $userTier
            ]);
            if (!$this->validateStageAccess($userTier, $stageId)) {
                \Log::warning('🛡️ [TIER_MIDDLEWARE] Access denied for sync current_module_id', [
                    'user_tier' => $userTier,
                    'stage_id' => $stageId,
                    'current_module_id' => $request->current_module_id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => "Your subscription tier does not allow access to Stage {$stageId}."
                ], 403);
            }
        }

        if ($request->has('completed_modules') && is_array($request->completed_modules)) {
            foreach ($request->completed_modules as $modId) {
                $stageId = (int) explode('.', $modId)[0];
                \Log::info('🛡️ [TIER_MIDDLEWARE] Checking progress sync completed_modules', [
                    'module_id' => $modId,
                    'stage_id' => $stageId,
                    'user_tier' => $userTier
                ]);
                if (!$this->validateStageAccess($userTier, $stageId)) {
                    \Log::warning('🛡️ [TIER_MIDDLEWARE] Access denied for completed_modules', [
                        'user_tier' => $userTier,
                        'stage_id' => $stageId,
                        'module_id' => $modId
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => "Your subscription tier does not allow access to Stage {$stageId} modules."
                    ], 403);
                }
            }
        }

        foreach (['unlocked_stages', 'completed_stages'] as $field) {
            if ($request->has($field) && is_array($request->input($field))) {
                foreach ($request->input($field) as $stageId) {
                    \Log::info("🛡️ [TIER_MIDDLEWARE] Checking progress sync {$field}", [
                        'stage_id' => $stageId,
                        'user_tier' => $userTier
                    ]);
                    if (!$this->validateStageAccess($userTier, $stageId)) {
                        \Log::warning("🛡️ [TIER_MIDDLEWARE] Access denied for {$field}", [
                            'user_tier' => $userTier,
                            'stage_id' => $stageId
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => "Your subscription tier does not allow access to Stage {$stageId}."
                        ], 403);
                    }
                }
            }
        }

        // 2. Check Community Hub Routes
        $hubId = null;
        $path = $request->getPathInfo();

        if (str_contains($path, '/community/hubs/')) {
            $hubId = $request->route('id') ?? $request->route('hubId');
        } elseif (str_contains($path, '/community/threads/')) {
            $threadId = $request->route('id') ?? $request->route('threadId');
            if ($threadId) {
                $thread = \App\Models\CommunityThread::find($threadId);
                if ($thread) {
                    $hubId = $thread->hub_id;
                }
            }
        } elseif (str_contains($path, '/community/replies/')) {
            $replyId = $request->route('id');
            if ($replyId) {
                $reply = \App\Models\CommunityReply::find($replyId);
                if ($reply && $reply->thread) {
                    $hubId = $reply->thread->hub_id;
                }
            }
        }

        if (!$hubId && $request->has('hub_id')) {
            $hubId = $request->input('hub_id');
        }

        if (!$hubId && $request->has('thread_id')) {
            $threadId = $request->input('thread_id');
            $thread = \App\Models\CommunityThread::find($threadId);
            if ($thread) {
                $hubId = $thread->hub_id;
            }
        }

        if ($hubId) {
            $hub = \App\Models\CommunityHub::find($hubId);
            if ($hub) {
                $requiredTier = $hub->access_level; // 'free', 'community', 'preparation'
                \Log::info('🛡️ [TIER_MIDDLEWARE] Checking community hub access', [
                    'hub_id' => $hubId,
                    'hub_name' => $hub->name,
                    'required_tier' => $requiredTier,
                    'user_tier' => $userTier
                ]);
                if (!$this->hasAccess($userTier, $requiredTier)) {
                    \Log::warning('🛡️ [TIER_MIDDLEWARE] Access denied for community hub', [
                        'user_tier' => $userTier,
                        'required_tier' => $requiredTier,
                        'hub_id' => $hubId,
                        'hub_name' => $hub->name
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Your subscription tier does not allow access to this community hub.'
                    ], 403);
                }
            }
        }

        \Log::info('🛡️ [TIER_MIDDLEWARE] Access granted successfully');
        return $next($request);
    }

    /**
     * Validate if the user tier can access the given stage ID
     */
    private function validateStageAccess(string $userTier, int $stageId): bool
    {
        $requiredTier = 'free';
        if ($stageId === 2) {
            $requiredTier = 'community';
        } elseif ($stageId > 2) {
            $requiredTier = 'preparation';
        }

        return $this->hasAccess($userTier, $requiredTier);
    }

    /**
     * Check tier hierarchy
     */
    private function hasAccess(string $userTier, string $requiredTier): bool
    {
        $tiers = [
            'free' => 0,
            'community' => 1,
            'preparation' => 2
        ];
        
        $userVal = $tiers[strtolower($userTier)] ?? 0;
        $reqVal = $tiers[strtolower($requiredTier)] ?? 0;
        
        return $userVal >= $reqVal;
    }
}
