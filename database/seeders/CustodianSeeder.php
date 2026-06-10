<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustodianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $custodians = [
            [
                'name' => 'Kofi Mensah',
                'email' => 'custodian@mailinator.com',
                'password' => Hash::make('password123'),
                'role' => 'custodian',
                'status' => 'active',
                'whatsapp' => '+233241234567',
                'instagram' => 'kofi_ancestral_roots',
                'linkedin' => 'kofi-mensah-genealogy',
                'picture' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&auto=format&fit=crop&q=80',
                'provider' => 'password',
                'location' => 'Accra, Greater Accra Region',
                'country' => 'Ghana',
                'years_experience' => 15,
                'specialty' => 'Genealogy & Clan Lineages',
                'avatar_initials' => 'KM',
                'avatar_class' => 'bg-emerald-500 text-white',
                'gradient_bg' => 'from-emerald-400 to-teal-600',
                'availability' => 'Available',
                'availability_text' => 'Available for custom lineage mapping and consultation.',
                'share_text' => 'Discover your Akan heritage and trace your lineage with me.',
                'description' => 'Elder Kofi Mensah has dedicated over 15 years to documenting and preserving Akan clan histories, oral traditions, and ancestral migrations in West Africa. He serves as a primary consultant for diaspora families seeking to reconnect with their specific family houses.',
                'tags' => ['Akan History', 'Clan Mapping', 'Oral Traditions', 'West Africa'],
                'price_from' => 50.00,
                'certification' => 'Certified Oral Historian - West African Heritage Council',
                'coc_status' => 'Active Council Member',
                'review_avg' => 4.95,
                'sessions_count' => 142,
                'verified' => true,
                'top_custodian' => true,
                'short_bio' => 'Custodian of Akan oral history and master clan lineage cartographer.',
                'about' => 'Born and raised in Kumasi, the heart of the Ashanti region, I grew up listening to the stories of our ancestors from the elders of the palace. Over the past two decades, I have formalized this knowledge to help brothers and sisters from the diaspora trace their family trees back to specific villages and houses. My methodology combines traditional oral history verification with modern archival records.',
                'languages' => ['English', 'Twi', 'Fante'],
                'services' => [
                    [
                        'name' => 'Ancestral Lineage Search & Verification',
                        'price' => 75.00,
                        'duration' => '90 mins',
                        'description' => 'A deep dive into your family history clues to map them to historical West African clans.'
                    ],
                    [
                        'name' => 'Traditional Naming Ceremony Guidance',
                        'price' => 120.00,
                        'duration' => '120 mins',
                        'description' => 'Guidance on your soul name based on your birth day and clan alignment.'
                    ]
                ],
                'testimonials' => [
                    [
                        'client_name' => 'Marcus Garvey Jr.',
                        'text' => 'Elder Kofi helped me trace my family\'s origin back to the Central Region. The details he provided were incredibly accurate and validated by local archives.',
                        'rating' => 5
                    ],
                    [
                        'client_name' => 'Amina Diop',
                        'text' => 'An absolute treasure of knowledge. The naming ceremony consultation changed my life.',
                        'rating' => 5
                    ]
                ]
            ],
            [
                'name' => 'Ngozi Adebayo',
                'email' => 'ngozi.adebayo@rootsafrica.com',
                'password' => Hash::make('password123'),
                'role' => 'custodian',
                'status' => 'active',
                'whatsapp' => '+2348039876543',
                'instagram' => 'ngozi_yoruba_heritage',
                'linkedin' => 'ngozi-adebayo-cultural',
                'picture' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500&auto=format&fit=crop&q=80',
                'provider' => 'password',
                'location' => 'Ibadan, Oyo State',
                'country' => 'Nigeria',
                'years_experience' => 12,
                'specialty' => 'Yoruba Culture & Spiritual Arts',
                'avatar_initials' => 'NA',
                'avatar_class' => 'bg-amber-500 text-white',
                'gradient_bg' => 'from-amber-400 to-orange-600',
                'availability' => 'Available',
                'availability_text' => 'Open for bookings on weekends.',
                'share_text' => 'Let us explore the wisdom of Ifa, Orisha traditions, and Yoruba philosophy.',
                'description' => 'Ngozi Adebayo is an author and traditional educator specializing in Yoruba philosophy, cosmology, and the symbolic language of Adire textile arts. She teaches the traditional wisdom systems to global audiences.',
                'tags' => ['Yoruba Philosophy', 'Orisha Traditions', 'Adire Art', 'Cosmology'],
                'price_from' => 60.00,
                'certification' => 'Licensed Cultural Consultant - Oyo State Ministry of Arts & Culture',
                'coc_status' => 'Certified Traditional Educator',
                'review_avg' => 4.88,
                'sessions_count' => 98,
                'verified' => true,
                'top_custodian' => true,
                'short_bio' => 'Yoruba cultural educator, Ifa philosopher, and traditional textile artist.',
                'about' => 'My mission is to demystify African spiritual and cultural traditions, presenting them as sophisticated systems of psychology, ecology, and community building. I specialize in Yoruba oral literature (Itan), philosophy, and creative arts, helping clients integrate these ancient principles into modern life.',
                'languages' => ['English', 'Yoruba'],
                'services' => [
                    [
                        'name' => 'Yoruba Philosophy & Cosmology Consultation',
                        'price' => 60.00,
                        'duration' => '60 mins',
                        'description' => 'Understand the core concepts of Ori, Destiny, and character development in Yoruba philosophy.'
                    ],
                    [
                        'name' => 'Traditional Symbols & Art Masterclass',
                        'price' => 90.00,
                        'duration' => '90 mins',
                        'description' => 'Learn the language of symbols used in traditional textiles and rituals.'
                    ]
                ],
                'testimonials' => [
                    [
                        'client_name' => 'Sarah Jenkins',
                        'text' => 'Sister Ngozi explained Yoruba cosmology in a way that resonated deeply with my personal healing journey. Highly recommend!',
                        'rating' => 5
                    ]
                ]
            ],
            [
                'name' => 'Mamadou Diallo',
                'email' => 'mamadou.diallo@rootsafrica.com',
                'password' => Hash::make('password123'),
                'role' => 'custodian',
                'status' => 'active',
                'whatsapp' => '+221776543210',
                'instagram' => 'mamadou_griot_tales',
                'linkedin' => 'mamadou-diallo-musicology',
                'picture' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=500&auto=format&fit=crop&q=80',
                'provider' => 'password',
                'location' => 'Dakar, Dakar Region',
                'country' => 'Senegal',
                'years_experience' => 20,
                'specialty' => 'Griot Oral History & Kora Music',
                'avatar_initials' => 'MD',
                'avatar_class' => 'bg-blue-500 text-white',
                'gradient_bg' => 'from-blue-400 to-indigo-600',
                'availability' => 'Available',
                'availability_text' => 'Available for educational webinars and private music consultations.',
                'share_text' => 'Listen to the ancient stories of the Sahel, accompanied by the 21 strings of the Kora.',
                'description' => 'Descended from a long line of Mandinka Griots (Jalis), Mamadou Diallo is a master Kora player and oral historian. He preserves the epic history of the Mali Empire and Sahelian kingdoms through music and storytelling.',
                'tags' => ['Griot Tradition', 'Kora Music', 'Mali Empire', 'Sahelian History'],
                'price_from' => 65.00,
                'certification' => 'National Living Treasure Designation - Senegalese Ministry of Culture',
                'coc_status' => 'Griot Guild Elder',
                'review_avg' => 5.00,
                'sessions_count' => 210,
                'verified' => true,
                'top_custodian' => true,
                'short_bio' => 'Master Kora player, Mandinka Griot, and oral historian of Sahelian kingdoms.',
                'about' => 'The Kora is not just an instrument; it is a library of our people\'s history. Every song tells a story of battles, peace treaties, family pacts, and ancient kings. I share these stories to keep our global African family informed of their rich, organized, and prosperous past.',
                'languages' => ['Wolof', 'French', 'Mandinka', 'English'],
                'services' => [
                    [
                        'name' => 'Griot Storytelling & History Session',
                        'price' => 65.00,
                        'duration' => '60 mins',
                        'description' => 'An interactive session detailing the history of the Mali and Songhai empires with live Kora accompaniment.'
                    ],
                    [
                        'name' => 'Kora Musical & Cultural Introduction',
                        'price' => 80.00,
                        'duration' => '60 mins',
                        'description' => 'A basic introduction to the tuning, playing, and history of the Kora instrument.'
                    ]
                ],
                'testimonials' => [
                    [
                        'client_name' => 'David Osei',
                        'text' => 'An absolute honor to learn from Mamadou. His mastery of both history and the Kora is unmatched.',
                        'rating' => 5
                    ]
                ]
            ],
            [
                'name' => 'Dr. Zola Dlamini',
                'email' => 'zola.dlamini@rootsafrica.com',
                'password' => Hash::make('password123'),
                'role' => 'custodian',
                'status' => 'active',
                'whatsapp' => '+27821234567',
                'instagram' => 'dr_zola_herbalism',
                'linkedin' => 'zola-dlamini-ethnobotany',
                'picture' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=500&auto=format&fit=crop&q=80',
                'provider' => 'password',
                'location' => 'Johannesburg, Gauteng',
                'country' => 'South Africa',
                'years_experience' => 18,
                'specialty' => 'Indigenous Ethnobotany & Bantu Lore',
                'avatar_initials' => 'ZD',
                'avatar_class' => 'bg-purple-500 text-white',
                'gradient_bg' => 'from-purple-400 to-pink-600',
                'availability' => 'Booked',
                'availability_text' => 'Booked for the next two weeks due to research field work.',
                'share_text' => 'Learn about the healing power of African flora and southern Bantu folklore.',
                'description' => 'Dr. Zola Dlamini holds a PhD in Ethnobotany and is also a registered traditional healer (Sangoma). She bridges the gap between academic scientific knowledge and ancient Southern African botanical healing traditions.',
                'tags' => ['Ethnobotany', 'Bantu Folklore', 'Herbal Medicine', 'Indigenous Knowledge'],
                'price_from' => 70.00,
                'certification' => 'Registered Practitioner - Traditional Health Practitioners Council of South Africa',
                'coc_status' => 'Board-Certified Ethnobotanist',
                'review_avg' => 4.92,
                'sessions_count' => 156,
                'verified' => true,
                'top_custodian' => false,
                'short_bio' => 'Ethnobotanist and traditional healer specializing in Southern African medicinal plants.',
                'about' => 'For thousands of years, our people used the forest as both a pharmacy and a temple. My work focuses on documenting the botanical wisdom of the Zulu, Xhosa, and Sotho peoples, verifying their ecological benefits, and sharing how we can live in greater harmony with nature.',
                'languages' => ['English', 'Zulu', 'Xhosa'],
                'services' => [
                    [
                        'name' => 'Introduction to Southern African Ethnobotany',
                        'price' => 70.00,
                        'duration' => '75 mins',
                        'description' => 'A webinar discussing native African plants, their historical uses, and current ecological preservation efforts.'
                    ]
                ],
                'testimonials' => [
                    [
                        'client_name' => 'Elena Rostova',
                        'text' => 'Incredibly educational session. Dr. Zola has a deep respect for both scientific rigour and traditional reverence.',
                        'rating' => 5
                    ]
                ]
            ],
            [
                'name' => 'Elimu Mwangi',
                'email' => 'elimu.mwangi@rootsafrica.com',
                'password' => Hash::make('password123'),
                'role' => 'custodian',
                'status' => 'active',
                'whatsapp' => '+254711223344',
                'instagram' => 'elimu_swahili_history',
                'linkedin' => 'elimu-mwangi-historian',
                'picture' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=500&auto=format&fit=crop&q=80',
                'provider' => 'password',
                'location' => 'Mombasa, Coast Province',
                'country' => 'Kenya',
                'years_experience' => 10,
                'specialty' => 'Swahili Maritime History & Bantu Linguistics',
                'avatar_initials' => 'EM',
                'avatar_class' => 'bg-amber-600 text-white',
                'gradient_bg' => 'from-amber-500 to-yellow-600',
                'availability' => 'Available',
                'availability_text' => 'Available for Swahili language immersion and trade history sessions.',
                'share_text' => 'Explore the Swahili Coast, its ancient trade links, and the rich Kiswahili language.',
                'description' => 'Elimu Mwangi is a linguistic researcher and historian focusing on Kiswahili and the historical interactions of the Swahili Coast with Indian Ocean trade networks. He provides engaging sessions on Bantu migrations and linguistics.',
                'tags' => ['Swahili Coast', 'Linguistics', 'Bantu Migrations', 'Indian Ocean Trade'],
                'price_from' => 40.00,
                'certification' => 'East African Historical Association Research Fellow',
                'coc_status' => 'Verified Researcher',
                'review_avg' => 4.90,
                'sessions_count' => 64,
                'verified' => true,
                'top_custodian' => false,
                'short_bio' => 'Linguistic historian and expert on Swahili coastal culture and trade history.',
                'about' => 'Kiswahili is a beautiful gateway to understanding East African identity. I help students, travelers, and diaspora members learn Kiswahili through the lens of history, exploring the coastlines, the dhow trade, and our connection to global history.',
                'languages' => ['Kiswahili', 'English', 'Luhya'],
                'services' => [
                    [
                        'name' => 'Kiswahili Language & Culture Quickstart',
                        'price' => 40.00,
                        'duration' => '60 mins',
                        'description' => 'Learn basic conversational Kiswahili along with important cultural etiquettes of East Africa.'
                    ],
                    [
                        'name' => 'Indian Ocean Trade & Swahili Coast History',
                        'price' => 55.00,
                        'duration' => '60 mins',
                        'description' => 'Learn the fascinating history of Kilwa, Zanzibar, and Mombasa maritime trading cities.'
                    ]
                ],
                'testimonials' => [
                    [
                        'client_name' => 'Jordan Peterson',
                        'text' => 'Elimu is an enthusiastic teacher! His breakdown of Kiswahili roots helped me pick up the language so much faster.',
                        'rating' => 5
                    ]
                ]
            ]
        ];

        foreach ($custodians as $custodianData) {
            User::updateOrCreate(
                ['email' => $custodianData['email']],
                $custodianData
            );
        }

        $this->command->info('Successfully seeded 5 custodians!');
    }
}
