<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    /**
     * Submit quiz responses and calculate scores on the backend
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'responses' => 'required|array',
            'responses.*.id' => 'required|integer',
            'responses.*.dim' => 'required|string',
            'responses.*.value' => 'required|numeric|min:1|max:4',
        ]);

        $name = $validated['name'];
        $responses = $validated['responses'];

        $scores = $this->calculateScores($responses);
        
        $totalScore = (int) round(
            ($scores['identity'] + $scores['emotional'] + $scores['authenticity'] + $scores['protocol'] + $scores['community']) / 5
        );

        $tierInfo = $this->getTierAndPersona($totalScore);

        $token = (string) Str::uuid();

        $reportData = [
            'name' => $name,
            'totalScore' => $totalScore,
            'scores' => $scores,
            'tier' => $tierInfo['tier'],
            'persona' => $tierInfo['persona'],
            'tier_display' => $tierInfo['tier_display'],
            'onboardingAnswers' => null,
        ];

        Cache::put("quiz:{$token}", $reportData, now()->addMinutes(30));

        return response()->json([
            'success' => true,
            'quiz_token' => $token,
        ]);
    }

    /**
     * Retrieve cached quiz report by token
     */
    public function getReport($token)
    {
        $reportData = Cache::get("quiz:{$token}");

        if (!$reportData) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz report expired or not found. Please retake the quiz.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $reportData,
        ]);
    }

    /**
     * Save guest onboarding answers to cached quiz report
     */
    public function saveOnboardingAnswers(Request $request)
    {
        $validated = $request->validate([
            'quiz_token' => 'required|string',
            'onboarding_answers' => 'required|array',
            'onboarding_answers.whatBroughtYouHere' => 'required|string',
            'onboarding_answers.travelTimeline' => 'required|string',
        ]);

        $token = $validated['quiz_token'];
        $answers = $validated['onboarding_answers'];

        $reportData = Cache::get("quiz:{$token}");

        if (!$reportData) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz report expired or not found. Please retake the quiz.',
            ], 404);
        }

        $reportData['onboardingAnswers'] = $answers;
        Cache::put("quiz:{$token}", $reportData, now()->addMinutes(30));

        return response()->json([
            'success' => true,
            'message' => 'Onboarding answers saved to quiz report successfully.',
        ]);
    }

    /**
     * Calculate score values per dimension (0-100 scale)
     */
    private function calculateScores(array $responses): array
    {
        $dimensions = [
            'identity' => [],
            'emotional' => [],
            'authenticity' => [],
            'protocol' => [],
            'community' => [],
        ];

        foreach ($responses as $r) {
            $dim = $r['dim'];
            if (array_key_exists($dim, $dimensions)) {
                $dimensions[$dim][] = (float) $r['value'];
            }
        }

        $avgScores = [];
        foreach ($dimensions as $key => $values) {
            $count = count($values);
            $avgValue = $count > 0 ? array_sum($values) / $count : 0.0;
            $avgScores[$key] = (int) round(($avgValue / 4.0) * 100);
        }

        return $avgScores;
    }

    /**
     * Resolve tier and persona from total score
     */
    private function getTierAndPersona(int $totalScore): array
    {
        if ($totalScore < 40) {
            return [
                'tier' => 'Latent',
                'persona' => 'Foundation Seeker',
                'tier_display' => 'Free',
            ];
        } elseif ($totalScore < 60) {
            return [
                'tier' => 'Active',
                'persona' => 'Cultural Explorer',
                'tier_display' => 'Community',
            ];
        } else {
            return [
                'tier' => 'Immersive',
                'persona' => 'Heritage Seeker',
                'tier_display' => 'Preparation',
            ];
        }
    }

    /**
     * Retrieve the authenticated user's quiz report
     */
    public function userReport(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->quiz_data) {
            return response()->json([
                'success' => false,
                'message' => 'No quiz report found for this user.',
            ], 404);
        }

        return response()->json($user->quiz_data);
    }
}
