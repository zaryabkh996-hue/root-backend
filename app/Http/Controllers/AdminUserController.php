<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class AdminUserController extends Controller
{
    public function getStats(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 1. Paying clients count
        $payingClientsCount = User::where('role', 'customer')
            ->where('subscription_tier', '!=', 'free')
            ->count();

        // 2. Active custodians count
        $activeCustodiansCount = User::where('role', 'custodian')
            ->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })
            ->count();

        // 3. Module completion rates
        $totalCustomers = User::where('role', 'customer')->count();

        $completionRates = [];
        $stages = [
            1 => 'Stage 1 · Emotional Preparation',
            2 => 'Stage 2 · Cultural Intelligence',
            3 => 'Stage 3 · Practical Preparation',
            4 => 'Stage 4 · Arrival Orientation',
            5 => 'Stage 5 · Heritage Journey',
            6 => 'Stage 6 · Post-Journey',
        ];

        // Fetch progress records with completed_stages to compute in PHP for safety across all DB types
        $progressRecords = UserProgress::whereHas('user', function ($q) {
            $q->where('role', 'customer');
        })->get(['completed_stages']);

        foreach ($stages as $stageId => $stageName) {
            $completedCount = 0;
            foreach ($progressRecords as $record) {
                $completed = $record->completed_stages ?? [];
                if (in_array($stageId, $completed)) {
                    $completedCount++;
                }
            }

            $rate = $totalCustomers > 0 ? round(($completedCount / $totalCustomers) * 100) : 0;
            $completionRates[] = [
                'name' => $stageName,
                'rate' => $rate,
            ];
        }

        return response()->json([
            'payingClients' => $payingClientsCount,
            'activeCustodians' => $activeCustodiansCount,
            'completionRates' => $completionRates,
        ]);
    }

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
            $tier = $user->subscription_tier ? ucfirst($user->subscription_tier) : 'Free';
            $phase = 'Immersive';
            $stage = 'Stage 1 · 0%';
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
                    $maxStage = max($completedStages);
                    $stage = "Stage {$maxStage} · 100%";
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
                'is_returned_traveller' => (bool)$user->is_returned_traveller,
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

    public function toggleReturnedTraveller(Request $request, $id)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $user = User::findOrFail($id);
            $user->is_returned_traveller = !$user->is_returned_traveller;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Returned Traveller status updated successfully',
                'is_returned_traveller' => (bool)$user->is_returned_traveller,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update status: ' . $e->getMessage()], 500);
        }
    }
}
