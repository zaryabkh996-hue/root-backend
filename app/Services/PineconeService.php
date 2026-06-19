<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * PineconeService — Professional REST API client for Pinecone vector database.
 *
 * Handles all vector operations: upsert, query, delete, and index health checks.
 * Uses the Pinecone Data Plane REST API v1 directly (no SDK dependency).
 *
 * @see https://docs.pinecone.io/reference/api
 */
class PineconeService
{
    private string $apiKey;
    private string $indexHost;
    private string $namespace;

    public function __construct()
    {
        $this->apiKey    = config('ai.pinecone.api_key');
        $this->namespace = config('ai.pinecone.namespace', 'knowledge-bank');

        if (empty($this->apiKey)) {
            throw new RuntimeException('PINECONE_API_KEY is not configured.');
        }

        // Build the index host URL
        // Pinecone serverless uses: https://{index-name}-{project-id}.svc.{environment}.pinecone.io
        // For the new API (2024+), we use the control plane to resolve the host
        $this->indexHost = $this->resolveIndexHost();
    }

    // ──────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────

    /**
     * Upsert a vector into Pinecone.
     *
     * @param string $id       Unique vector ID (e.g. "kb-{contribution_id}")
     * @param array  $vector   1536-dimensional embedding array
     * @param array  $metadata Key-value metadata stored alongside the vector
     */
    public function upsert(string $id, array $vector, array $metadata = []): bool
    {
        $payload = [
            'vectors' => [
                [
                    'id'       => $id,
                    'values'   => $vector,
                    'metadata' => $metadata,
                ],
            ],
            'namespace' => $this->namespace,
        ];

        $response = $this->request('POST', '/vectors/upsert', $payload);

        Log::info('Pinecone upsert successful', [
            'id'        => $id,
            'namespace' => $this->namespace,
        ]);

        return true;
    }

    /**
     * Batch upsert multiple vectors.
     *
     * @param array $vectors Array of ['id' => string, 'values' => array, 'metadata' => array]
     */
    public function batchUpsert(array $vectors): bool
    {
        // Pinecone recommends batches of 100 max
        $batches = array_chunk($vectors, 100);

        foreach ($batches as $batch) {
            $payload = [
                'vectors'   => $batch,
                'namespace' => $this->namespace,
            ];

            $this->request('POST', '/vectors/upsert', $payload);
        }

        Log::info('Pinecone batch upsert successful', [
            'total_vectors' => count($vectors),
            'batches'       => count($batches),
        ]);

        return true;
    }

    /**
     * Query Pinecone for similar vectors.
     *
     * @param array $vector        Query embedding (1536 dimensions)
     * @param int   $topK          Number of results to return
     * @param array $filter        Metadata filter (e.g. ['status' => 'embedded'])
     * @param bool  $includeMetadata Whether to return metadata with results
     *
     * @return array{matches: array} Pinecone query response
     */
    public function query(
        array $vector,
        int $topK = 3,
        array $filter = [],
        bool $includeMetadata = true
    ): array {
        $payload = [
            'vector'          => $vector,
            'topK'            => $topK,
            'includeMetadata' => $includeMetadata,
            'namespace'       => $this->namespace,
        ];

        if (!empty($filter)) {
            $payload['filter'] = $filter;
        }

        $response = $this->request('POST', '/query', $payload);

        Log::debug('Pinecone query completed', [
            'topK'      => $topK,
            'matches'   => count($response['matches'] ?? []),
            'namespace' => $this->namespace,
        ]);

        return $response;
    }

    /**
     * Delete a vector by ID.
     */
    public function delete(string $id): bool
    {
        $payload = [
            'ids'       => [$id],
            'namespace' => $this->namespace,
        ];

        $this->request('POST', '/vectors/delete', $payload);

        Log::info('Pinecone delete successful', ['id' => $id]);

        return true;
    }

    /**
     * Delete multiple vectors by ID.
     */
    public function batchDelete(array $ids): bool
    {
        $payload = [
            'ids'       => $ids,
            'namespace' => $this->namespace,
        ];

        $this->request('POST', '/vectors/delete', $payload);

        Log::info('Pinecone batch delete successful', ['count' => count($ids)]);

        return true;
    }

    /**
     * Describe index statistics — useful for health checks and monitoring.
     */
    public function describeIndex(): array
    {
        return $this->request('POST', '/describe_index_stats', []);
    }

    // ──────────────────────────────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Resolve the Pinecone index host URL via the control plane API.
     */
    private function resolveIndexHost(): string
    {
        $indexName = config('ai.pinecone.index', 'ourroots-knowledge-bank');

        $response = Http::withHeaders([
            'Api-Key' => $this->apiKey,
        ])
        ->timeout(15)
        ->get("https://api.pinecone.io/indexes/{$indexName}");

        if ($response->successful()) {
            $host = $response->json('host');
            if ($host) {
                return "https://{$host}";
            }
        }

        // Fallback: construct host from environment (older Pinecone setup)
        $environment = config('ai.pinecone.environment', 'us-east-1');
        Log::warning('Pinecone host resolution failed, using constructed URL', [
            'index'       => $indexName,
            'environment' => $environment,
            'status'      => $response->status(),
        ]);

        return "https://{$indexName}.svc.{$environment}.pinecone.io";
    }

    /**
     * Make an authenticated HTTP request to the Pinecone Data Plane API.
     *
     * Includes retry logic (3 attempts) for transient failures.
     */
    private function request(string $method, string $path, array $payload): array
    {
        $maxRetries = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Api-Key'      => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(30)
                ->send($method, "{$this->indexHost}{$path}", [
                    'json' => $payload,
                ]);

                if ($response->successful()) {
                    return $response->json() ?? [];
                }

                // Non-retryable errors (4xx except 429)
                if ($response->status() >= 400 && $response->status() < 500 && $response->status() !== 429) {
                    Log::error('Pinecone API client error', [
                        'path'    => $path,
                        'status'  => $response->status(),
                        'body'    => $response->body(),
                    ]);
                    throw new RuntimeException(
                        "Pinecone API error ({$response->status()}): {$response->body()}"
                    );
                }

                // Retryable errors (5xx, 429)
                $lastException = new RuntimeException(
                    "Pinecone API error ({$response->status()}): {$response->body()}"
                );

                Log::warning("Pinecone request failed, retrying (attempt {$attempt}/{$maxRetries})", [
                    'path'   => $path,
                    'status' => $response->status(),
                ]);

            } catch (RuntimeException $e) {
                throw $e; // Re-throw non-retryable
            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning("Pinecone request exception, retrying (attempt {$attempt}/{$maxRetries})", [
                    'path'    => $path,
                    'message' => $e->getMessage(),
                ]);
            }

            // Exponential backoff: 500ms, 1000ms, 2000ms
            if ($attempt < $maxRetries) {
                usleep(500000 * pow(2, $attempt - 1));
            }
        }

        throw new RuntimeException(
            "Pinecone request failed after {$maxRetries} attempts: " . ($lastException?->getMessage() ?? 'Unknown error')
        );
    }
}
