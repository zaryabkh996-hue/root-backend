<?php

namespace App\Http\Controllers;

use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProgressController extends Controller
{
    /**
     * Get the authenticated user's progress.
     * Returns null data if no progress record exists yet.
     */
    public function show(Request $request)
    {
        $progress = UserProgress::where('user_id', $request->user()->id)->first();

        if (!$progress) {
            return response()->json([
                'success' => true,
                'data'    => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatProgress($progress),
        ]);
    }

    /**
     * Full upsert — sync the entire progress object from the client.
     * Used on first load and as a fallback catch-all sync.
     */
    public function sync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'completed_modules'  => 'nullable|array',
            'completed_modules.*'=> 'string|max:20',
            'current_module_id'  => 'nullable|string|max:20',
            'journal_entries'    => 'nullable|array',
            'feedback_entries'   => 'nullable|array',
            'unlocked_stages'    => 'nullable|array',
            'unlocked_stages.*'  => 'integer|between:1,6',
            'completed_stages'   => 'nullable|array',
            'completed_stages.*' => 'integer|between:1,6',
            'afro_score'         => 'nullable|integer|between:0,100',
            'user_persona'       => 'nullable|string|max:100',
            'lifecycle_phase'    => 'nullable|string|max:100',
            'started_at'         => 'nullable|string',
            'last_active_at'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $userId = $request->user()->id;

        $progress = UserProgress::firstOrNew(['user_id' => $userId]);

        // Only update fields that were explicitly sent
        if ($request->has('completed_modules'))  $progress->completed_modules  = $request->completed_modules ?? [];
        if ($request->has('current_module_id'))  $progress->current_module_id  = $request->current_module_id ?? '1.1';
        if ($request->has('feedback_entries'))   $progress->feedback_entries   = $request->feedback_entries ?? [];
        if ($request->has('user_persona'))       $progress->user_persona       = $request->user_persona;
        if ($request->has('lifecycle_phase'))    $progress->lifecycle_phase    = $request->lifecycle_phase;
        if ($request->has('last_active_at'))     $progress->last_active_at     = $request->last_active_at;

        // Recalculate progress server-side for safety
        $this->recalculateProgress($progress, $request->user()->role);

        // Journal entries use the encrypted accessor
        if ($request->has('journal_entries')) {
            $progress->journal_entries = $request->journal_entries ?? [];
        }

        if (!$progress->started_at) {
            $progress->started_at = $request->started_at ?? now();
        }

        $progress->save();

        return response()->json([
            'success' => true,
            'data'    => $this->formatProgress($progress),
        ]);
    }

    /**
     * Mark a single module as complete.
     * Fast, granular action — no full payload needed.
     */
    public function completeModule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'module_id'        => 'required|string|max:20',
            'next_module_id'   => 'nullable|string|max:20',
            'unlocked_stages'  => 'nullable|array',
            'completed_stages' => 'nullable|array',
            'afro_score'       => 'nullable|integer|between:0,100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $userId   = $request->user()->id;
        $progress = UserProgress::firstOrNew(['user_id' => $userId]);

        if (!$progress->started_at) {
            $progress->started_at = now();
        }

        $completed = $progress->completed_modules ?? [];
        if (!in_array($request->module_id, $completed)) {
            $completed[] = $request->module_id;
            $progress->completed_modules = $completed;
        }

        if ($request->filled('next_module_id')) {
            $progress->current_module_id = $request->next_module_id;
        }

        // Recalculate progress server-side for safety
        $this->recalculateProgress($progress, $request->user()->role);

        $progress->last_active_at = now();
        $progress->save();

        return response()->json([
            'success' => true,
            'data'    => $this->formatProgress($progress),
        ]);
    }

    /**
     * Save a single journal entry for a module (encrypted at rest).
     */
    public function saveJournal(Request $request, string $moduleId)
    {
        $validator = Validator::make(['module_id' => $moduleId, 'text' => $request->text], [
            'module_id' => 'required|string|max:20',
            'text'      => 'present|string|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $userId   = $request->user()->id;
        $progress = UserProgress::firstOrNew(['user_id' => $userId]);

        if (!$progress->started_at) {
            $progress->started_at = now();
        }

        $entries              = $progress->journal_entries ?? [];
        $entries[$moduleId]   = $request->text;
        $progress->journal_entries = $entries;
        $progress->last_active_at  = now();
        $progress->save();

        return response()->json(['success' => true]);
    }

    /**
     * Save a single feedback (reaction) entry for a module.
     */
    public function saveFeedback(Request $request, string $moduleId)
    {
        $validator = Validator::make([
            'module_id' => $moduleId,
            'key'       => $request->key,
        ], [
            'module_id' => 'required|string|max:20',
            'key'       => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $userId   = $request->user()->id;
        $progress = UserProgress::firstOrNew(['user_id' => $userId]);

        if (!$progress->started_at) {
            $progress->started_at = now();
        }

        $entries            = $progress->feedback_entries ?? [];
        $entries[$moduleId] = $request->key;
        $progress->feedback_entries = $entries;
        $progress->last_active_at   = now();
        $progress->save();

        return response()->json(['success' => true]);
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function formatProgress(UserProgress $p): array
    {
        return [
            'completed_modules'  => $p->completed_modules  ?? [],
            'current_module_id'  => $p->current_module_id  ?? '1.1',
            'journal_entries'    => $p->journal_entries     ?? [],
            'feedback_entries'   => $p->feedback_entries    ?? [],
            'unlocked_stages'    => $p->unlocked_stages     ?? [1],
            'completed_stages'   => $p->completed_stages    ?? [],
            'afro_score'         => $p->afro_score          ?? 0,
            'user_persona'       => $p->user_persona        ?? 'Heritage Seeker',
            'lifecycle_phase'    => $p->lifecycle_phase     ?? 'Foundation Building',
            'started_at'         => $p->started_at?->toISOString(),
            'last_active_at'     => $p->last_active_at?->toISOString(),
        ];
    }

    /**
     * Recalculate stage completion server-side for security and forgery prevention
     */
    private function recalculateProgress(UserProgress $progress, string $role): void
    {
        $completedModules = $progress->completed_modules ?? [];

        if ($role === 'custodian') {
            $completedStages = [];
            $unlockedStages = [1];
            for ($s = 1; $s <= 6; $s++) {
                if (in_array(strval($s), $completedModules)) {
                    $completedStages[] = $s;
                    if ($s + 1 <= 6) {
                        $unlockedStages[] = $s + 1;
                    }
                }
            }
            $progress->completed_stages = array_values(array_unique($completedStages));
            $progress->unlocked_stages = array_values(array_unique($unlockedStages));
            $progress->afro_score = (int) min(100, round((count($completedModules) / 6) * 100));
        } else {
            $stageModules = [
                1 => ['1.1', '1.2', '1.3', '1.4', '1.5'],
                2 => ['2.1', '2.2', '2.3', '2.4', '2.5', '2.6', '2.7', '2.8'],
                3 => ['3.1', '3.2', '3.3', '3.4', '3.5', '3.6'],
                4 => ['4.1', '4.2', '4.3', '4.4', '4.5', '4.6'],
                5 => ['5.1', '5.2', '5.3', '5.4', '5.5', '5.6', '5.7'],
                6 => ['6.1', '6.2', '6.3', '6.4', '6.5'],
            ];

            $completedStages = [];
            $unlockedStages = [1];

            foreach ($stageModules as $stageId => $modules) {
                $allCompleted = true;
                foreach ($modules as $mod) {
                    if (!in_array($mod, $completedModules)) {
                        $allCompleted = false;
                        break;
                    }
                }
                if ($allCompleted) {
                    $completedStages[] = $stageId;
                }
            }

            sort($completedStages);
            foreach ($completedStages as $cs) {
                if ($cs + 1 <= 6 && !in_array($cs + 1, $unlockedStages)) {
                    $unlockedStages[] = $cs + 1;
                }
            }

            $progress->completed_stages = array_values(array_unique($completedStages));
            $progress->unlocked_stages = array_values(array_unique($unlockedStages));
            $progress->afro_score = (int) min(100, round((count($completedModules) / 37) * 100));
        }
    }
}
