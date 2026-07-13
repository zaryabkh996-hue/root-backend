<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeContribution;
use App\Services\KnowledgeBankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * KnowledgeBankController — Handles Knowledge Bank contribution submissions
 * and admin embedding operations.
 *
 * Custodian routes:
 *   POST   /api/knowledge-bank/submit             — Submit contribution
 *   GET    /api/knowledge-bank/my-contributions    — List own contributions
 *   GET    /api/knowledge-bank/contributions/{id}  — Get single contribution
 *
 * Admin routes:
 *   POST   /api/admin/knowledge-bank/embed/{id}    — Embed approved contribution
 *   GET    /api/admin/knowledge-bank/contributions  — List all contributions
 *   PUT    /api/admin/knowledge-bank/contributions/{id}/status — Update status
 */
class KnowledgeBankController extends Controller
{
    public function __construct(
        private KnowledgeBankService $knowledgeBankService,
    ) {}

    // ──────────────────────────────────────────────────────────────
    // Custodian Routes
    // ──────────────────────────────────────────────────────────────

    private function validateCustodian(Request $request): bool
    {
        $user = $request->user();
        return $user && $user->role === 'custodian' && $user->status === 'active' && (bool)$user->verified;
    }

    /**
     * Submit a new Knowledge Bank contribution.
     *
     * Called from the Custodian Contribute page (Step 4: Review & Submit).
     */
    public function submit(Request $request): JsonResponse
    {
        if (!$this->validateCustodian($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Active and verified Custodian role required.'
            ], 403);
        }

        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'required|string|min:50',
            'category'          => 'required|string|in:ceremony,language,food,dress,sites,music',
            'ethnic_group'      => 'required|string|max:100',
            'region'            => 'required|string|max:100',
            'authority_role'    => 'required|string|max:255',
            'media_file'        => 'nullable|file|max:51200|mimes:mp4,mov,avi,webm,mp3,wav,ogg,m4a,jpg,jpeg,png,webp',
            'consent_signed'    => 'required|boolean|accepted',
            'consent_signature' => 'required|string|max:255',
        ]);

        $user = $request->user();

        // Handle file upload if present
        $mediaPath = null;
        $mediaType = null;

        if ($request->hasFile('media_file')) {
            $file = $request->file('media_file');

            // Run malware scan
            if (!\App\Helpers\VirusScanner::scan($file)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Malware scan failed: the uploaded file contains a security threat.',
                ], 400);
            }

            $extension = strtolower($file->getClientOriginalExtension());

            // Determine media type
            $mediaType = match (true) {
                in_array($extension, ['mp4', 'mov', 'avi', 'webm']) => 'video',
                in_array($extension, ['mp3', 'wav', 'ogg', 'm4a'])  => 'audio',
                in_array($extension, ['jpg', 'jpeg', 'png', 'webp']) => 'image',
                default => 'other',
            };

            $mediaPath = $file->store('knowledge-bank/media', 'public');
        }

        $contribution = $this->knowledgeBankService->submitContribution(
            data: array_merge($validated, [
                'media_path' => $mediaPath,
                'media_type' => $mediaType,
            ]),
            custodian: $user,
        );

        return response()->json([
            'success' => true,
            'message' => 'Your knowledge has been submitted successfully. It will be reviewed by the Knowledge Review Board.',
            'data'    => [
                'id'       => $contribution->id,
                'title'    => $contribution->title,
                'category' => $contribution->category,
                'status'   => $contribution->status,
            ],
        ], 201);
    }

    /**
     * Get the authenticated custodian's own contributions.
     */
    public function myContributions(Request $request): JsonResponse
    {
        if (!$this->validateCustodian($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Active and verified Custodian role required.'
            ], 403);
        }

        $contributions = KnowledgeContribution::where('user_id', $request->user()->id)
            ->withCount('citations')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($c) => [
                'id'             => $c->id,
                'title'          => $c->title,
                'category'       => $c->category,
                'status'         => $c->status,
                'citation_count' => $c->citations_count,
                'created_at'     => $c->created_at->toISOString(),
                'embedded_at'    => $c->embedded_at?->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'data'    => $contributions,
        ]);
    }

    /**
     * Get a single contribution detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if (!$this->validateCustodian($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Active and verified Custodian role required.'
            ], 403);
        }

        $contribution = KnowledgeContribution::where('user_id', $request->user()->id)
            ->withCount('citations')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $contribution->id,
                'title'            => $contribution->title,
                'description'      => $contribution->description,
                'category'         => $contribution->category,
                'ethnic_group'     => $contribution->ethnic_group,
                'region'           => $contribution->region,
                'authority_role'   => $contribution->authority_role,
                'media_path'       => $contribution->media_path,
                'media_type'       => $contribution->media_type,
                'status'           => $contribution->status,
                'review_count'     => $contribution->review_count,
                'citation_count'   => $contribution->citations_count,
                'created_at'       => $contribution->created_at->toISOString(),
                'embedded_at'      => $contribution->embedded_at?->toISOString(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Admin Routes
    // ──────────────────────────────────────────────────────────────

    /**
     * List all contributions (admin view).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = KnowledgeContribution::with('custodian:id,name,email')
            ->withCount('citations')
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        $contributions = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $contributions,
        ]);
    }

    /**
     * Update contribution status (admin review action).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:submitted,under_review,approved,rejected',
        ]);

        $contribution = KnowledgeContribution::findOrFail($id);
        $oldStatus = $contribution->status;

        $contribution->update([
            'status' => $validated['status'],
        ]);

        // If being reviewed, increment review count
        if ($validated['status'] === 'approved') {
            $reviewedBy = $contribution->reviewed_by ?? [];
            $reviewedBy[] = $request->user()->id;
            $contribution->update([
                'reviewed_by'  => array_unique($reviewedBy),
                'review_count' => count(array_unique($reviewedBy)),
            ]);
        }

        Log::info('Knowledge contribution status updated', [
            'contribution_id' => $id,
            'old_status'      => $oldStatus,
            'new_status'      => $validated['status'],
            'admin_id'        => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Contribution status updated to '{$validated['status']}'.",
            'data'    => [
                'id'     => $contribution->id,
                'status' => $contribution->status,
            ],
        ]);
    }

    /**
     * Embed an approved contribution into Pinecone (admin action).
     *
     * This generates the OpenAI embedding and upserts the vector
     * into Pinecone, making it available for Amen AI retrieval.
     */
    public function embed(Request $request, int $id): JsonResponse
    {
        $contribution = KnowledgeContribution::findOrFail($id);

        if (!in_array($contribution->status, ['approved', 'embedded'])) {
            return response()->json([
                'success' => false,
                'message' => "Cannot embed contribution with status '{$contribution->status}'. Must be 'approved' first.",
            ], 422);
        }

        try {
            $this->knowledgeBankService->embedContribution($contribution);

            return response()->json([
                'success' => true,
                'message' => 'Contribution has been embedded in Pinecone and is now available to Amen AI.',
                'data'    => [
                    'id'          => $contribution->id,
                    'pinecone_id' => $contribution->fresh()->pinecone_id,
                    'status'      => 'embedded',
                    'embedded_at' => $contribution->fresh()->embedded_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to embed contribution', [
                'contribution_id' => $id,
                'error'           => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to embed contribution: ' . $e->getMessage(),
            ], 500);
        }
    }
}
