<?php

namespace Database\Seeders;

use App\Models\CommunityHub;
use App\Models\CommunityThread;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommunityThreadSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $custodian = User::where('role', 'custodian')->first();

        if (!$admin || !$custodian) {
            echo "Admin or Custodian user not found. Please run DatabaseSeeder first.\n";
            return;
        }

        $hubs = CommunityHub::all();
        if ($hubs->isEmpty()) {
            echo "No community hubs found. Please run CommunityHubSeeder first.\n";
            return;
        }

        $loveHub = $hubs->where('slug', 'the-love-hub')->first();
        $citizenshipHub = $hubs->where('slug', 'the-citizenship-hub')->first();
        $businessHub = $hubs->where('slug', 'the-business-hub')->first();

        $threads = [
            [
                'hub_id' => $loveHub->id ?? $hubs->first()->id,
                'user_id' => $custodian->id,
                'title' => 'Understanding Akan Greetings and Protocols',
                'content' => 'In Ghana, greetings are a vital protocol. When entering a room, you must greet from right to left. Always use your right hand to shake hands or hand items to elders. What are your experiences with learning these protocols?',
                'location' => 'Kumasi, Ghana',
                'user_stage' => 'Stage 2',
                'user_tier' => 'Community',
                'is_active' => true,
                'is_pinned' => true,
            ],
            [
                'hub_id' => $citizenshipHub->id ?? $hubs->first()->id,
                'user_id' => $admin->id,
                'title' => 'Applying for Ghana Visa: Tips and Documents Needed',
                'content' => 'Applying for a visa to Ghana can be confusing. Make sure you have your yellow fever certificate, passport valid for at least 6 months, and reference letters. Post your questions here about the visa application process and Accra airport arrival protocols!',
                'location' => 'New York, USA',
                'user_stage' => 'Stage 3',
                'user_tier' => 'Preparation',
                'is_active' => true,
                'is_pinned' => false,
            ],
            [
                'hub_id' => $businessHub->id ?? $hubs->first()->id,
                'user_id' => $custodian->id,
                'title' => 'Starting a Business in Accra: Mobile Money & Banking',
                'content' => 'Accra is a thriving hub for startup business ideas. However, setting up mobile money (Momo) and merchant accounts requires specific local documents. Ask your questions about Ghanaian business regulations here.',
                'location' => 'Accra, Ghana',
                'user_stage' => 'Stage 3',
                'user_tier' => 'Community',
                'is_active' => true,
                'is_pinned' => false,
            ],
        ];

        foreach ($threads as $thread) {
            CommunityThread::updateOrCreate(
                ['title' => $thread['title']],
                $thread
            );
        }

        echo "Community threads seeded successfully.\n";
    }
}
