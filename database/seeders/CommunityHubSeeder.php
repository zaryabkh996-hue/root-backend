<?php

namespace Database\Seeders;

use App\Models\CommunityHub;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommunityHubSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        if (!$admin) {
            echo "No admin user found. Please create an admin user first.\n";
            return;
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

        foreach ($hubs as $hub) {
            CommunityHub::create([
                ...$hub,
                'created_by' => $admin->id,
                'is_active' => true,
            ]);
        }

        echo "Community hubs seeded successfully.\n";
    }
}
