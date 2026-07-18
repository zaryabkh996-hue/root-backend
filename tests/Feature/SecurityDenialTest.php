<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CommunityHub;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecurityDenialTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test EnsureAdminRole middleware prevents non-admin users from admin routes.
     */
    public function test_non_admin_cannot_access_admin_endpoints(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Unauthorized. Admin role required.'
                 ]);
    }

    /**
     * Test EnsureReturnedTraveller middleware prevents non-returned-travellers from stories creation.
     */
    public function test_non_returned_traveller_cannot_access_stories_endpoints(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'is_returned_traveller' => false
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/user/stories', [
            'title' => 'My story',
            'body' => 'Story body'
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Forbidden. Returned Traveller status required.'
                 ]);
    }

    /**
     * Test CheckSubscriptionTier middleware prevents free tier users from accessing Stage 2+ progress sync.
     */
    public function test_free_tier_user_cannot_sync_stage2_progress(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'subscription_tier' => 'free'
        ]);

        Sanctum::actingAs($user);

        // Stage 2.1 is Stage 2, which requires community tier or higher.
        $response = $this->putJson('/api/progress', [
            'current_module_id' => '2.1.intro'
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Your subscription tier does not allow access to Stage 2.'
                 ]);
    }

    /**
     * Test CheckSubscriptionTier middleware prevents free tier users from accessing community hubs requiring higher tier.
     */
    public function test_free_tier_user_cannot_access_paid_community_hub(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'subscription_tier' => 'free'
        ]);

        Sanctum::actingAs($user);

        $hub = CommunityHub::create([
            'name' => 'Preparation Hub',
            'slug' => 'preparation-hub',
            'adinkra' => 'symbol',
            'emoji' => '🌍',
            'description' => 'Preparation only hub',
            'access_level' => 'preparation',
            'access_label' => 'Preparation',
            'border_color' => 'gold',
            'created_by' => $user->id
        ]);

        $response = $this->getJson("/api/community/hubs/{$hub->id}");

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Your subscription tier does not allow access to this community hub.'
                 ]);
    }
}
