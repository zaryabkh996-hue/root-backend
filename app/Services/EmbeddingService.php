<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * EmbeddingService — OpenAI text embedding generation.
 *
 * Uses the text-embedding-3-small model (1536 dimensions) for both:
 * - Knowledge Bank contribution storage (upsert to Pinecone)
 * - Query-time semantic search (embed user message → query Pinecone)
 *
 * The model choice must match the Pinecone index dimensions (1536).
 *
 * @see https://platform.openai.com/docs/guides/embeddings
 */
class EmbeddingService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('ai.openai.api_key');
        $this->model  = config('ai.embedding_model', 'text-embedding-3-small');

        if (empty($this->apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }
    }

    /**
     * Generate a single embedding vector for a text input.
     *
     * @param string $text The text to embed
     * @return array 1536-dimensional float array
     */
    public function generateEmbedding(string $text): array
    {
        // Clean and truncate text (embedding models have token limits)
        $cleanText = $this->prepareText($text);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])
        ->timeout(30)
        ->post('https://api.openai.com/v1/embeddings', [
            'model' => $this->model,
            'input' => $cleanText,
        ]);

        if (!$response->successful()) {
            Log::error('OpenAI Embedding API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException(
                "OpenAI Embedding API error ({$response->status()}): {$response->body()}"
            );
        }

        $data = $response->json();
        $embedding = $data['data'][0]['embedding'] ?? null;

        if (!$embedding || !is_array($embedding)) {
            throw new RuntimeException('Invalid embedding response from OpenAI.');
        }

        Log::debug('Embedding generated', [
            'model'      => $this->model,
            'dimensions' => count($embedding),
            'text_length' => strlen($cleanText),
            'usage'      => $data['usage'] ?? [],
        ]);

        return $embedding;
    }

    /**
     * Generate embeddings for multiple texts in a single API call.
     *
     * @param array $texts Array of strings to embed
     * @return array Array of 1536-dimensional float arrays
     */
    public function generateBatchEmbeddings(array $texts): array
    {
        $cleanTexts = array_map(fn($text) => $this->prepareText($text), $texts);

        // OpenAI supports up to 2048 inputs per batch
        $batches = array_chunk($cleanTexts, 2048);
        $allEmbeddings = [];

        foreach ($batches as $batch) {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ])
            ->timeout(60)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => $this->model,
                'input' => $batch,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI Batch Embedding API error', [
                    'status'     => $response->status(),
                    'body'       => $response->body(),
                    'batch_size' => count($batch),
                ]);
                throw new RuntimeException(
                    "OpenAI Embedding API error ({$response->status()}): {$response->body()}"
                );
            }

            $data = $response->json();

            foreach ($data['data'] as $item) {
                $allEmbeddings[] = $item['embedding'];
            }
        }

        Log::info('Batch embeddings generated', [
            'total'  => count($allEmbeddings),
            'model'  => $this->model,
        ]);

        return $allEmbeddings;
    }

    /**
     * Prepare text for embedding — clean whitespace, truncate to safe length.
     *
     * text-embedding-3-small supports up to 8191 tokens (~32,000 chars).
     * We truncate conservatively at 28,000 chars.
     */
    private function prepareText(string $text): string
    {
        // Normalize whitespace
        $clean = preg_replace('/\s+/', ' ', trim($text));

        // Truncate to stay within token limits
        if (strlen($clean) > 28000) {
            $clean = substr($clean, 0, 28000);
            Log::warning('Text truncated for embedding', [
                'original_length' => strlen($text),
                'truncated_to'    => 28000,
            ]);
        }

        return $clean;
    }
}
