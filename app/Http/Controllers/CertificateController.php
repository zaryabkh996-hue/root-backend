<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProgress;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    protected $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Get certificate info for current user
     */
    public function getInfo(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $progress = UserProgress::where('user_id', $user->id)->first();

            if (!$progress) {
                return response()->json([
                    'success' => false,
                    'message' => 'No progress found',
                ], 404);
            }

            $info = $this->certificateService->getCertificateInfo($user, $progress);

            return response()->json([
                'success' => true,
                'data' => $info,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get certificate info',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download certificate PDF
     */
    public function download(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $progress = UserProgress::where('user_id', $user->id)->first();

            if (!$progress) {
                return response()->json([
                    'success' => false,
                    'message' => 'No progress found',
                ], 404);
            }

            // Check if user has completed any stages
            $completedStages = $progress->completed_stages ?? [];
            if (count($completedStages) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No completed stages yet. Complete at least one stage to earn a certificate.',
                ], 400);
            }

            // Generate PDF
            $pdf = $this->certificateService->generatePdf($user, $progress);

            // Return PDF download
            return $pdf->download('Heritage-Readiness-Certificate-' . $user->id . '.pdf');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate certificate',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all certificates (for future expansion - multiple certificate types)
     */
    public function listCertificates(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $progress = UserProgress::where('user_id', $user->id)->first();

            if (!$progress) {
                return response()->json([
                    'success' => false,
                    'data' => [],
                ], 200);
            }

            $completedStages = $progress->completed_stages ?? [];
            $certificates = [];

            // Heritage Readiness Certificate
            if (count($completedStages) > 0) {
                $certificates[] = [
                    'type' => 'heritage-readiness',
                    'title' => 'Heritage Readiness Certificate',
                    'description' => count($completedStages) === 6 
                        ? 'Completion Certificate - All 6 Stages'
                        : 'Progress Certificate - ' . count($completedStages) . ' of 6 Stages',
                    'issuedDate' => now()->format('Y-m-d'),
                    'downloadUrl' => '/api/certificates/download',
                    'canDownload' => true,
                ];
            }

            // Day Name Certificate (only after journey completion or naming ceremony)
            // This is a placeholder for future implementation
            $certificates[] = [
                'type' => 'day-name',
                'title' => 'Day Name Certificate',
                'description' => 'Awaits naming ceremony',
                'issuedDate' => null,
                'downloadUrl' => null,
                'canDownload' => false,
                'locked' => true,
            ];

            return response()->json([
                'success' => true,
                'data' => $certificates,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list certificates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
