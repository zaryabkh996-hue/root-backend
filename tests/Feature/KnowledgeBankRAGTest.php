<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EmbeddingService;
use App\Services\KnowledgeBankService;
use App\Services\PineconeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class KnowledgeBankRAGTest extends TestCase
{
    use RefreshDatabase;

    public function test_rag_filtering_excludes_inactive_or_unverified_custodians()
    {
        // 1. Create custodians
        $activeCustodian = User::factory()->create([
            'role' => 'custodian',
            'status' => 'active',
            'coc_status' => 'approved',
            'email_verified_at' => now(),
        ]);
        $activeCustodian->verified = true;
        $activeCustodian->save();

        $bannedCustodian = User::factory()->create([
            'role' => 'custodian',
            'status' => 'suspended',
        ]);
        $bannedCustodian->verified = true;
        $bannedCustodian->save();

        $unverifiedCustodian = User::factory()->create([
            'role' => 'custodian',
            'status' => 'active',
        ]);
        $unverifiedCustodian->verified = false;
        $unverifiedCustodian->save();

        // 2. Mock EmbeddingService and PineconeService
        $mockEmbedding = Mockery::mock(EmbeddingService::class);
        $mockEmbedding->shouldReceive('generateEmbedding')
            ->andReturn(array_fill(0, 1536, 0.1));
        $this->app->instance(EmbeddingService::class, $mockEmbedding);

        $mockPinecone = Mockery::mock(PineconeService::class);
        
        // Scenario A: Pinecone returns match belonging to banned custodian
        $mockPinecone->shouldReceive('query')
            ->once()
            ->andReturn([
                'matches' => [
                    [
                        'score' => 0.85,
                        'metadata' => [
                            'contribution_id' => 10,
                            'custodian_user_id' => $bannedCustodian->id,
                            'custodian_name' => 'Banned Custodian',
                            'title' => 'Banned Lore',
                            'fragment_text' => 'Banned content'
                        ]
                    ]
                ]
            ]);
        $this->app->instance(PineconeService::class, $mockPinecone);

        // Run service call
        $service = $this->app->make(KnowledgeBankService::class);
        $result = $service->queryKnowledgeBank('hello');

        // Banned custodian's fragment should be skipped (returns null since no active matches)
        $this->assertNull($result);

        // Scenario B: Pinecone returns matches from both unverified and active custodians
        $mockPinecone2 = Mockery::mock(PineconeService::class);
        $mockPinecone2->shouldReceive('query')
            ->once()
            ->andReturn([
                'matches' => [
                    [
                        'score' => 0.95,
                        'metadata' => [
                            'contribution_id' => 11,
                            'custodian_user_id' => $unverifiedCustodian->id,
                            'custodian_name' => 'Unverified Custodian',
                            'title' => 'Unverified Lore',
                            'fragment_text' => 'Unverified content'
                        ]
                    ],
                    [
                        'score' => 0.90,
                        'metadata' => [
                            'contribution_id' => 12,
                            'custodian_user_id' => $activeCustodian->id,
                            'custodian_name' => 'Active Custodian',
                            'title' => 'Active Lore',
                            'fragment_text' => 'Active content'
                        ]
                    ]
                ]
            ]);
        $this->app->instance(PineconeService::class, $mockPinecone2);

        $service2 = $this->app->make(KnowledgeBankService::class);
        $result2 = $service2->queryKnowledgeBank('hello');

        // Should skip the first match (unverified) and return the second match (active)
        $this->assertNotNull($result2);
        $this->assertEquals($activeCustodian->id, $result2['custodian_user_id']);
        $this->assertEquals(12, $result2['contribution_id']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
