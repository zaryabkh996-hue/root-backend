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
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@portal.com',
            'password' => Hash::make('admin@portal.com'),
            'role' => 'admin',
        ]);

        // Create Test Customer
        User::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('customer@example.com'),
            'role' => 'customer',
        ]);

        // Create Test Custodians
        User::create([
            'name' => 'Akosua O.',
            'email' => 'akosua@ourroots.com',
            'password' => Hash::make('password'),
            'role' => 'custodian',
            'location' => 'Accra',
            'country' => 'Ghana',
            'years_experience' => 12,
            'specialty' => 'Heritage sites',
            'avatar_class' => 'avatar-photo',
            'gradient_bg' => 'linear-gradient(160deg,#3a1f0a 0%,#1a0f05 40%,#5c3a1a 100%)',
            'availability' => 'Available',
            'description' => 'Heritage educator. Cape Coast & Elmina specialist. 200+ diaspora relatives guided through the Door of No Return.',
            'tags' => json_encode(['Heritage sites', 'Twi · Fante']),
            'price_from' => 80.00,
            'certification' => '✓ Certified',
            'coc_status' => '✓ Good',
            'review_avg' => 4.9,
            'sessions_count' => 47,
        ]);

        User::create([
            'name' => 'Kwame B.',
            'email' => 'kwame@ourroots.com',
            'password' => Hash::make('password'),
            'role' => 'custodian',
            'location' => 'Kumasi',
            'country' => 'Ghana',
            'years_experience' => 8,
            'specialty' => 'Naming ceremony',
            'avatar_class' => 'avatar-photo-2',
            'gradient_bg' => 'linear-gradient(160deg,#2a1f0a 0%,#1a1205 40%,#4a3a15 100%)',
            'availability' => 'Available',
            'description' => 'Ashanti cultural protocol. Naming ceremonies, traditional courts. Trauma-informed with grief-counselling background.',
            'tags' => json_encode(['Naming ceremony', 'Twi']),
            'price_from' => 95.00,
            'certification' => '✓ Certified',
            'coc_status' => '✓ Good',
            'review_avg' => 4.7,
            'sessions_count' => 31,
        ]);

        User::create([
            'name' => 'Nia M.',
            'email' => 'nia@ourroots.com',
            'password' => Hash::make('password'),
            'role' => 'custodian',
            'location' => 'Lagos',
            'country' => 'Nigeria',
            'years_experience' => 6,
            'specialty' => 'Genealogy',
            'avatar_class' => 'avatar-photo-3',
            'gradient_bg' => 'linear-gradient(160deg,#0a1e0f 0%,#051008 40%,#1a3a1f 100%)',
            'availability' => 'Booked',
            'description' => 'Yoruba family-history researcher. DNA-to-village mapping for diaspora seeking genealogical roots.',
            'tags' => json_encode(['Genealogy', 'Yoruba · Igbo']),
            'price_from' => 120.00,
            'certification' => '✓ Certified',
            'coc_status' => '✓ Good',
            'review_avg' => 4.8,
            'sessions_count' => 28,
        ]);

        User::create([
            'name' => 'Mama Efua.',
            'email' => 'efua@ourroots.com',
            'password' => Hash::make('password'),
            'role' => 'custodian',
            'location' => 'Cape Coast',
            'country' => 'Ghana',
            'years_experience' => 15,
            'specialty' => 'Heritage sites',
            'avatar_class' => 'avatar-photo-4',
            'gradient_bg' => 'linear-gradient(160deg,#3a1a0a 0%,#1a0a05 40%,#5c2a15 100%)',
            'availability' => 'Available',
            'description' => 'Cultural advisor & ritual facilitator. Specialises in pre-castle emotional preparation and post-castle integration.',
            'tags' => json_encode(['Heritage sites', 'Spiritual']),
            'price_from' => 110.00,
            'certification' => '✓ Certified',
            'coc_status' => '✓ Good',
            'review_avg' => 5.0,
            'sessions_count' => 65,
        ]);

        User::create([
            'name' => 'Solomon W.',
            'email' => 'solomon@ourroots.com',
            'password' => Hash::make('password'),
            'role' => 'custodian',
            'location' => 'Addis Ababa',
            'country' => 'Ethiopia',
            'years_experience' => 9,
            'specialty' => 'Heritage sites',
            'avatar_class' => 'avatar-photo',
            'gradient_bg' => 'linear-gradient(160deg,#1a0f2a 0%,#0a0515 40%,#2a1a40 100%)',
            'availability' => 'Available',
            'description' => 'Ethiopian Orthodox heritage. Lalibela rock churches, Axum, Rastafari connection for Caribbean diaspora.',
            'tags' => json_encode(['Heritage sites', 'Amharic']),
            'price_from' => 90.00,
            'certification' => '✓ Certified',
            'coc_status' => '✓ Good',
            'review_avg' => 4.6,
            'sessions_count' => 22,
        ]);

        User::create([
            'name' => 'Fatou D.',
            'email' => 'fatou@ourroots.com',
            'password' => Hash::make('password'),
            'role' => 'custodian',
            'location' => 'Dakar',
            'country' => 'Senegal',
            'years_experience' => 7,
            'specialty' => 'Heritage sites',
            'avatar_class' => 'avatar-photo-6',
            'gradient_bg' => 'linear-gradient(160deg,#0a2a1a 0%,#051510 40%,#1a4a2f 100%)',
            'availability' => 'Available',
            'description' => 'Senegambian Wolof scholar. Gorée Island specialist. Bridges French-speaking diaspora and Anglophone returnees.',
            'tags' => json_encode(['Heritage sites', 'Wolof · French']),
            'price_from' => 85.00,
            'certification' => '✓ Certified',
            'coc_status' => '✓ Good',
            'review_avg' => 4.5,
            'sessions_count' => 19,
        ]);
    }
}
