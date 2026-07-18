<?php

namespace App\Services;

use App\Models\KnowledgeContribution;
use App\Models\KnowledgeCitation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * KnowledgeBankService — Manages the full Knowledge Bank pipeline.
 *
 * Handles:
 * - Contribution submission (from Custodian Contribute page)
 * - Embedding approved contributions into Pinecone
 * - Querying Pinecone for relevant fragments during Amen AI chat
 * - Logging citations and triggering $0.35 payouts
 *
 * This is the bridge between the Custodian contribution flow and Amen AI retrieval.
 */
class KnowledgeBankService
{
    public function __construct(
        private EmbeddingService $embeddingService,
        private PineconeService $pineconeService,
    ) {}

    // ──────────────────────────────────────────────────────────────
    // Contribution Submission
    // ──────────────────────────────────────────────────────────────

    /**
     * Submit a new Knowledge Bank contribution from a Custodian.
     *
     * Creates the database record with status 'submitted'.
     * The contribution then enters the review pipeline.
     */
    public function submitContribution(array $data, User $custodian): KnowledgeContribution
    {
        $contribution = KnowledgeContribution::create([
            'user_id'           => $custodian->id,
            'title'             => $data['title'],
            'description'       => $data['description'],
            'category'          => $data['category'],
            'ethnic_group'      => $data['ethnic_group'],
            'region'            => $data['region'],
            'authority_role'    => $data['authority_role'],
            'media_path'        => $data['media_path'] ?? null,
            'media_type'        => $data['media_type'] ?? null,
            'consent_signed'    => $data['consent_signed'] ?? false,
            'consent_signature' => $data['consent_signature'] ?? null,
            'status'            => 'submitted',
        ]);

        Log::info('Knowledge Bank contribution submitted', [
            'contribution_id' => $contribution->id,
            'custodian_id'    => $custodian->id,
            'custodian_name'  => $custodian->name,
            'category'        => $data['category'],
            'title'           => $data['title'],
        ]);

        return $contribution;
    }

    // ──────────────────────────────────────────────────────────────
    // Embedding Pipeline
    // ──────────────────────────────────────────────────────────────

    /**
     * Embed an approved contribution into Pinecone.
     *
     * This is called by an admin action after the Knowledge Review Board
     * has approved the contribution (≥5 validators).
     *
     * Steps:
     * 1. Build rich text for embedding (title + description + context)
     * 2. Generate embedding via OpenAI text-embedding-3-small
     * 3. Upsert vector + metadata into Pinecone
     * 4. Update contribution status to 'embedded'
     */
    public function embedContribution(KnowledgeContribution $contribution): void
    {
        // Guard: only embed approved contributions
        if (!in_array($contribution->status, ['approved', 'embedded'])) {
            throw new \InvalidArgumentException(
                "Cannot embed contribution with status '{$contribution->status}'. Must be 'approved'."
            );
        }

        $custodian = $contribution->custodian;

        // Build rich embedding text — includes contextual metadata for better retrieval
        $embeddingText = $this->buildEmbeddingText($contribution);

        // Generate embedding vector
        $vector = $this->embeddingService->generateEmbedding($embeddingText);

        // Build Pinecone metadata (stored alongside the vector for retrieval)
        $pineconeId = 'kb-' . $contribution->id;
        $metadata = [
            'contribution_id'  => $contribution->id,
            'custodian_user_id' => $custodian->id,
            'custodian_name'   => $custodian->name,
            'custodian_region' => $contribution->region,
            'ethnic_group'     => $contribution->ethnic_group,
            'category'         => $contribution->category,
            'title'            => $contribution->title,
            'fragment_text'    => $contribution->description,
            'authority_role'   => $contribution->authority_role,
            'status'           => 'embedded',
            'verified'         => true,
        ];

        // Upsert to Pinecone
        $this->pineconeService->upsert($pineconeId, $vector, $metadata);

        // Update contribution status
        $contribution->update([
            'status'      => 'embedded',
            'pinecone_id' => $pineconeId,
            'embedded_at' => now(),
        ]);

        Log::info('Knowledge Bank contribution embedded in Pinecone', [
            'contribution_id' => $contribution->id,
            'pinecone_id'     => $pineconeId,
            'custodian_name'  => $custodian->name,
            'vector_dims'     => count($vector),
        ]);
    }

    /**
     * Remove a contribution's vector from Pinecone.
     */
    public function removeFromPinecone(KnowledgeContribution $contribution): void
    {
        if ($contribution->pinecone_id) {
            $this->pineconeService->delete($contribution->pinecone_id);

            $contribution->update([
                'status'      => 'approved', // Revert to approved
                'pinecone_id' => null,
                'embedded_at' => null,
            ]);

            Log::info('Knowledge Bank contribution removed from Pinecone', [
                'contribution_id' => $contribution->id,
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Knowledge Bank Query (used by AmenAIService)
    // ──────────────────────────────────────────────────────────────

    /**
     * Query the Knowledge Bank for fragments relevant to a client's message.
     *
     * Returns the top matching fragment (if above confidence threshold)
     * or null if no match meets the threshold.
     *
     * @return array|null Fragment data or null
     */
    public function queryKnowledgeBank(string $message): ?array
    {
        try {
            // Generate embedding for the client's message
            $queryVector = $this->embeddingService->generateEmbedding($message);

            // Query Pinecone with metadata filter
            $results = $this->pineconeService->query(
                vector: $queryVector,
                topK: 3,
                filter: [
                    'status'   => ['$eq' => 'embedded'],
                    'verified' => ['$eq' => true],
                ],
            );

            $matches = $results['matches'] ?? [];

            if (empty($matches)) {
                Log::debug('Knowledge Bank query: no matches found', [
                    'message_preview' => Str::limit($message, 100),
                ]);
                return null;
            }

            // Find the first match whose authoring custodian is active and verified
            $topMatch = null;
            $score = 0.0;
            $metadata = [];
            
            foreach ($matches as $match) {
                $matchMetadata = $match['metadata'] ?? [];
                $custodianId = $matchMetadata['custodian_user_id'] ?? null;
                
                if ($custodianId) {
                    $custodian = User::find($custodianId);
                    if ($custodian && $custodian->role === 'custodian' && $custodian->status === 'active' && (bool)$custodian->verified) {
                        $topMatch = $match;
                        $score = $match['score'] ?? 0.0;
                        $metadata = $matchMetadata;
                        break;
                    }
                }
            }

            if (!$topMatch) {
                Log::info('Knowledge Bank query: no matches from active/verified custodians.');
                return null;
            }

            $confidenceThreshold = config('ai.kb_confidence_threshold', 0.78);
            $nearMissThreshold = config('ai.kb_near_miss_threshold', 0.60);

            // Log near-misses for Knowledge Bank gap analysis (§12.6)
            if ($score >= $nearMissThreshold && $score < $confidenceThreshold) {
                Log::info('Knowledge Bank near-miss (gap analysis)', [
                    'score'           => $score,
                    'threshold'       => $confidenceThreshold,
                    'message_preview' => Str::limit($message, 100),
                    'closest_match'   => $metadata['title'] ?? 'unknown',
                ]);
            }

            // Apply confidence threshold
            if ($score < $confidenceThreshold) {
                Log::debug('Knowledge Bank query: below threshold', [
                    'score'     => $score,
                    'threshold' => $confidenceThreshold,
                ]);
                return null;
            }

            Log::info('Knowledge Bank fragment matched', [
                'score'           => $score,
                'contribution_id' => $metadata['contribution_id'] ?? null,
                'custodian_name'  => $metadata['custodian_name'] ?? 'unknown',
                'title'           => $metadata['title'] ?? 'unknown',
            ]);

            return [
                'contribution_id'   => $metadata['contribution_id'] ?? null,
                'custodian_user_id' => $metadata['custodian_user_id'] ?? null,
                'custodian_name'    => $metadata['custodian_name'] ?? '',
                'custodian_region'  => $metadata['custodian_region'] ?? '',
                'ethnic_group'      => $metadata['ethnic_group'] ?? '',
                'category'          => $metadata['category'] ?? '',
                'title'             => $metadata['title'] ?? '',
                'fragment_text'     => $metadata['fragment_text'] ?? '',
                'authority_role'    => $metadata['authority_role'] ?? '',
                'score'             => $score,
            ];

        } catch (\Exception $e) {
            // Graceful degradation — if Pinecone is down, Amen still works
            Log::error('Knowledge Bank query failed — falling back to general knowledge', [
                'error'   => $e->getMessage(),
                'message' => Str::limit($message, 100),
            ]);
            return null;
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Citation Logging
    // ──────────────────────────────────────────────────────────────

    /**
     * Log a Knowledge Bank citation when Amen uses a fragment.
     * This triggers the $0.35 payout queue for the Custodian.
     */
    public function logCitation(
        int $contributionId,
        int $custodianId,
        int $clientId,
        string $conversationId,
        float $pineconeScore
    ): KnowledgeCitation {
        // 1. Validate Attribution: verify contribution exists and belongs to the custodian
        $contribution = KnowledgeContribution::find($contributionId);
        if (!$contribution || $contribution->user_id !== $custodianId) {
            throw new \InvalidArgumentException("Invalid citation attribution: contribution does not belong to the custodian.");
        }

        $payoutAmount = config('ai.citation_payout_usd', 0.35);
        $payoutStatus = 'pending';

        // 2. Session Uniqueness / Idempotency check:
        $existsInSession = KnowledgeCitation::where('conversation_id', $conversationId)
            ->where('contribution_id', $contributionId)
            ->exists();
        
        if ($existsInSession) {
            $payoutAmount = 0.00;
            $payoutStatus = 'failed';
            Log::info('Knowledge Bank citation payout marked as duplicate (session idempotency)', [
                'conversation_id' => $conversationId,
                'contribution_id' => $contributionId,
            ]);
        }

        // 3. Farming Protection / Cooldown:
        if ($payoutStatus === 'pending') {
            $existsRecently = KnowledgeCitation::where('client_id', $clientId)
                ->where('contribution_id', $contributionId)
                ->where('payout_amount', '>', 0)
                ->where('created_at', '>=', now()->subHours(24))
                ->exists();
            
            if ($existsRecently) {
                $payoutAmount = 0.00;
                $payoutStatus = 'failed';
                Log::info('Knowledge Bank citation payout blocked to prevent reward farming (24h client-contribution cooldown)', [
                    'client_id'       => $clientId,
                    'contribution_id' => $contributionId,
                ]);
            }
        }

        $citation = KnowledgeCitation::create([
            'contribution_id' => $contributionId,
            'custodian_id'    => $custodianId,
            'client_id'       => $clientId,
            'conversation_id' => $conversationId,
            'pinecone_score'  => $pineconeScore,
            'payout_amount'   => $payoutAmount,
            'payout_status'   => $payoutStatus,
            'cited_at'        => now(),
        ]);

        Log::info('Knowledge Bank citation logged', [
            'citation_id'     => $citation->id,
            'contribution_id' => $contributionId,
            'custodian_id'    => $custodianId,
            'client_id'       => $clientId,
            'payout_amount'   => $citation->payout_amount,
            'payout_status'   => $citation->payout_status,
            'pinecone_score'  => $pineconeScore,
        ]);

        return $citation;
    }

    // ──────────────────────────────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Build rich embedding text from a contribution.
     *
     * Includes title, category, region, and description for
     * better semantic matching at query time.
     */
    private function buildEmbeddingText(KnowledgeContribution $contribution): string
    {
        $parts = [
            "Title: {$contribution->title}",
            "Category: {$contribution->category}",
            "Cultural Group: {$contribution->ethnic_group}",
            "Region: {$contribution->region}",
            "Knowledge: {$contribution->description}",
        ];

        // Include transcription if available (from audio/video)
        if ($contribution->transcription) {
            $parts[] = "Transcription: {$contribution->transcription}";
        }

        return implode("\n", $parts);
    }
}
