<?php

namespace App\Http\Controllers;

use App\Models\CommunityHub;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SeederController extends Controller
{
    /**
     * Seed community hubs to database
     * Access via: GET /api/seed/community-hubs
     */
    public function seedCommunityHubs()
    {
        try {
            Log::info('🌱 [SEEDER] Starting community hubs seeding...');

            $admin = User::where('role', 'admin')->first();

            if (!$admin) {
                Log::warning('⚠️ [SEEDER] No admin user found');
                return response()->json([
                    'success' => false,
                    'message' => 'No admin user found. Please create an admin user first.'
                ], 400);
            }

            $hubs = [
                [
                    'name' => 'The Love Hub',
                    'slug' => 'the-love-hub',
                    'adinkra' => 'Akoma',
                    'emoji' => '💕',
                    'description' => 'Dive deep into the foundations of love and explore the roots of connection and compassion in African traditions. Share your journey of understanding love, intimacy, and relationships through the lens of ancestral wisdom.',
                    'access_level' => 'community',
                    'access_label' => 'Read free · post Community+',
                    'border_color' => '#E8937F',
                ],
                [
                    'name' => 'The Citizenship Hub',
                    'slug' => 'the-citizenship-hub',
                    'adinkra' => 'Gye Nyame',
                    'emoji' => '🏛️',
                    'description' => 'Explore what it means to be a citizen and your role in building thriving communities. Discuss civic participation, social responsibility, and collective action rooted in African philosophies of ubuntu.',
                    'access_level' => 'community',
                    'access_label' => 'Read free · post Community+',
                    'border_color' => '#8B6F47',
                ],
                [
                    'name' => 'The Business Hub',
                    'slug' => 'the-business-hub',
                    'adinkra' => 'Duafe',
                    'emoji' => '💼',
                    'description' => 'Build your entrepreneurial journey with guidance rooted in African business principles. Share business ideas, challenges, and victories as you create wealth and impact in your communities.',
                    'access_level' => 'community',
                    'access_label' => 'Read free · post Community+',
                    'border_color' => '#D4A574',
                ],
                [
                    'name' => 'The Foodie Hub',
                    'slug' => 'the-foodie-hub',
                    'adinkra' => 'Nyame Dua',
                    'emoji' => '🍽️',
                    'description' => 'Celebrate African cuisine and the stories behind our traditional foods. Share recipes, farming stories, and the cultural significance of food in our heritage.',
                    'access_level' => 'community',
                    'access_label' => 'Read free · post Community+',
                    'border_color' => '#9B7653',
                ],
                [
                    'name' => 'The Solo Hub',
                    'slug' => 'the-solo-hub',
                    'adinkra' => 'Akofena',
                    'emoji' => '🗡️',
                    'description' => 'Find strength in solitude and navigate your personal journey of self-discovery. Share insights about independence, personal growth, and finding your unique path.',
                    'access_level' => 'community',
                    'access_label' => 'Read free · post Community+',
                    'border_color' => '#B8860B',
                ],
                [
                    'name' => 'The Prosperity Hub',
                    'slug' => 'the-prosperity-hub',
                    'adinkra' => 'Anan Ntoso',
                    'emoji' => '✨',
                    'description' => 'Manifest abundance and explore the African understanding of prosperity beyond material wealth. Share strategies for financial health, purpose-driven living, and creating legacy.',
                    'access_level' => 'community',
                    'access_label' => 'Read free · post Community+',
                    'border_color' => '#D4AF37',
                ],
            ];

            $createdCount = 0;
            foreach ($hubs as $hub) {
                // Check if hub already exists by slug
                $existingHub = CommunityHub::where('slug', $hub['slug'])->first();
                
                if (!$existingHub) {
                    CommunityHub::create([
                        ...$hub,
                        'created_by' => $admin->id,
                        'is_active' => true,
                    ]);
                    $createdCount++;
                    Log::info('✅ [SEEDER] Hub created: ' . $hub['name']);
                } else {
                    Log::info('ℹ️ [SEEDER] Hub already exists: ' . $hub['name']);
                }
            }

            Log::info('✅ [SEEDER] Community hubs seeding completed', [
                'created' => $createdCount,
                'total' => count($hubs)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Community hubs seeded successfully!',
                'created' => $createdCount,
                'total' => count($hubs)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [SEEDER] Community hubs seeding failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Seeding failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
