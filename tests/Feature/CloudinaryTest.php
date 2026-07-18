<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloudinaryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test signature generation endpoint is protected and works when authenticated.
     */
    public function test_signature_requires_auth()
    {
        $response = $this->postJson('/api/cloudinary/signature', [
            'upload_preset' => 'test_preset'
        ]);

        $response->assertStatus(401);
    }

    public function test_signature_generation_works_when_authenticated()
    {
        $user = User::factory()->create([
            'role' => 'admin'
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/cloudinary/signature', [
                'upload_preset' => 'test_preset'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'signature',
                'timestamp',
                'api_key',
                'upload_preset'
            ])
            ->assertJson([
                'success' => true,
                'upload_preset' => 'test_preset'
            ]);
    }

    /**
     * Test delivery URL generation endpoint is protected and works when authenticated.
     */
    public function test_delivery_url_requires_auth()
    {
        $response = $this->postJson('/api/cloudinary/delivery-url', [
            'public_id' => 'sample_video'
        ]);

        $response->assertStatus(401);
    }

    public function test_delivery_url_works_when_authenticated()
    {
        $user = User::factory()->create([
            'role' => 'admin'
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/cloudinary/delivery-url', [
                'public_id' => 'sample_video',
                'resource_type' => 'video'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'url'
            ])
            ->assertJson([
                'success' => true
            ]);

        $this->assertStringContainsString('sample_video', $response->json('url'));
        $this->assertStringContainsString('authenticated', $response->json('url'));
    }
}
