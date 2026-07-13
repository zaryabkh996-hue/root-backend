<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Library;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create Admin User
        $admin = User::where('email', 'admin@portal.com')->first();
        if (!$admin) {
            $seedPassword = env('ADMIN_SEED_PASSWORD');
            if (!$seedPassword || strlen($seedPassword) < 12 || $seedPassword === 'admin@portal.com') {
                // Generate a secure random password if not configured securely or if it is the insecure default
                $seedPassword = \Illuminate\Support\Str::random(16);
                $this->command->info("Generated secure Admin password: {$seedPassword}");
            }
            User::forceCreate([
                'name' => 'Admin',
                'email' => 'admin@portal.com',
                'password' => Hash::make($seedPassword),
                'role' => 'admin',
            ]);
        }

        $this->call([
            CustodianSeeder::class,
            CommunityHubSeeder::class,
            CommunityThreadSeeder::class,
        ]);
    }
}
