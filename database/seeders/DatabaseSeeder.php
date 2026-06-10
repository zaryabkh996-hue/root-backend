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

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@portal.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin@portal.com'),
                'role' => 'admin',
            ]
        );

        $this->call([
            CustodianSeeder::class,
        ]);
    }
}
