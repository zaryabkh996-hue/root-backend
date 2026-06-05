<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class AdminUserController extends Controller
{
    public function getUsers(Request $request)
    {
        if (!$request->user()) {
            Log::error('AdminUserController::getUsers - No authenticated user');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $role = $request->query('role', 'customer');
        $page = (int) $request->query('page', 1);
        $limit = (int) $request->query('limit', 10);
        $search = $request->query('search', '');
        $query = User::where('role', $role)
            ->with('progress');

        // Search by name or email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get total count before pagination
        $total = $query->count();

        // Apply pagination with eager loading
        $users = $query->paginate($limit, ['*'], 'page', $page);

        // Transform users to match frontend format
        $formattedUsers = $users->map(function ($user) {
            // Generate initials from name
            $names = explode(' ', trim($user->name));
            $initials = '';
            foreach (array_slice($names, 0, 2) as $name) {
                $initials .= strtoupper(substr($name, 0, 1));
            }

            // Get progress data if available
            $progress = $user->progress;
            $tier = 'Preparation';
            $phase = 'Immersive';
            $stage = 'Stage 1 · 60%';
            $score = '0';

            if ($progress) {
                // Map lifecycle_phase to phase
                $phaseMap = [
                    'immersive' => 'Immersive',
                    'integration' => 'Integration',
                    'community' => 'Community',
                ];
                $phase = $phaseMap[$progress->lifecycle_phase] ?? 'Immersive';

                // Use afro_score if available
                $score = (string) round($progress->afro_score ?? 0);

                // Calculate current stage from completed_stages
                $completedStages = $progress->completed_stages ?? [];
                if (!empty($completedStages)) {
                    $maxStage = max(array_keys($completedStages));
                    $stageProgress = ($completedStages[$maxStage] ?? 0) * 100;
                    $stage = "Stage {$maxStage} · {$stageProgress}%";
                }
            }

            return [
                'id' => $user->id,
                'initials' => $initials,
                'name' => $user->name,
                'location' => 'US/Canada · Heritage Seeker',
                'tier' => $tier,
                'phase' => $phase,
                'stage' => $stage,
                'score' => $score,
            ];
        });

        return response()->json([
            'users' => $formattedUsers,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => ceil($total / $limit),
            'perPage' => $limit,
        ]);
    }
}
