<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CommunityThread;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    private $trackToStageId = [
        'Emotional Preparation' => 1,
        'Cultural Intelligence' => 2,
        'Practical Preparation' => 3,
        'Arrival Orientation' => 4,
        'Heritage Journey Experience' => 5,
        'Post Journey Experience' => 6,
        'Post-Journey Integration' => 6,
    ];

    /**
     * GET /api/search?q={query}&type={modules|library|custodians|community}
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $type = $request->query('type', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [
                    'modules' => [],
                    'library' => [],
                    'custodians' => [],
                    'community' => [],
                ]
            ]);
        }

        $results = [
            'modules' => [],
            'library' => [],
            'custodians' => [],
            'community' => [],
        ];

        // 1. Search Modules & Library (Sanity or static fallback)
        if (empty($type) || $type === 'modules' || $type === 'library') {
            $sanityModules = $this->searchSanityModules($query);
            $user = auth()->user();
            $userTier = $user ? ($user->subscription_tier ?? 'free') : 'free';
            $role = $user ? $user->role : 'customer';
            
            foreach ($sanityModules as $m) {
                $stageId = $this->trackToStageId[$m['track'] ?? ''] ?? 1;
                $modId = isset($m['moduleNumber']) ? "{$stageId}.{$m['moduleNumber']}" : ($m['_id'] ?? $m['id'] ?? '');
                
                // Tier filter check (applied before listing modules/library results)
                if ($role !== 'admin' && !$this->hasTierAccess($userTier, $stageId)) {
                    continue;
                }

                $formatted = [
                    'id' => $modId,
                    'title' => $m['title'] ?? '',
                    'slug' => $m['slug'] ?? '',
                    'stage' => $stageId,
                    'type' => $m['contentType'] ?? $m['type'] ?? 'Story Module',
                ];
                
                $results['modules'][] = $formatted;
                
                // Determine if library item
                $typeLower = strtolower($formatted['type']);
                if ($typeLower === 'audio' || $typeLower === 'video' || $typeLower === 'pdf') {
                    $results['library'][] = $formatted;
                }
            }

            // Cap at 20
            $results['modules'] = array_slice($results['modules'], 0, 20);
            $results['library'] = array_slice($results['library'], 0, 20);
        }

        // 2. Search Custodians
        if (empty($type) || $type === 'custodians') {
            $results['custodians'] = array_slice($this->searchCustodians($query), 0, 20);
        }

        // 3. Search Community
        if (empty($type) || $type === 'community') {
            $results['community'] = array_slice($this->searchCommunity($query), 0, 20);
        }

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Search modules from Sanity or fallback to local static modules
     */
    private function searchSanityModules(string $query): array
    {
        $projectId = env('SANITY_PROJECT_ID');
        $dataset = env('SANITY_DATASET', 'production');
        $apiVersion = env('SANITY_API_VERSION', '2026-05-22');
        $token = env('SANITY_TOKEN');

        $sanityModules = [];

        if ($projectId && $token) {
            try {
                $escapedQuery = str_replace('"', '\\"', $query);
                $groq = '*[_type == "module" && status == "published" && (title match "' . $escapedQuery . '*" || subtitle match "' . $escapedQuery . '*" || body match "' . $escapedQuery . '*" || takeaways match "' . $escapedQuery . '*")]';
                $url = "https://{$projectId}.api.sanity.io/v{$apiVersion}/data/query/{$dataset}?query=" . urlencode($groq);
                
                $response = Http::timeout(3)->withToken($token)->get($url);
                if ($response->successful()) {
                    $sanityModules = $response->json()['result'] ?? [];
                }
            } catch (\Exception $e) {
                Log::warning('SearchController::searchSanityModules - Sanity query failed: ' . $e->getMessage());
            }
        }

        if (empty($sanityModules)) {
            $sanityModules = array_filter($this->getStaticModules(), function ($m) use ($query) {
                $q = strtolower($query);
                return str_contains(strtolower($m['title'] ?? ''), $q) ||
                       str_contains(strtolower($m['body'] ?? ''), $q) ||
                       str_contains(strtolower($m['type'] ?? ''), $q);
            });
        }

        return $sanityModules;
    }

    /**
     * Search community threads using TSVECTOR (PostgreSQL) or fallback LIKE (MySQL/SQLite)
     */
    private function searchCommunity(string $query): array
    {
        $driver = \DB::connection()->getDriverName();
        $user = auth()->user();
        $userId = $user ? $user->id : null;
        $role = $user ? $user->role : 'customer';
        $userTier = $user ? ($user->subscription_tier ?? 'free') : 'free';

        // 1. Get accessible hub IDs based on user's tier
        $accessibleHubIds = \DB::table('community_hubs')
            ->where('is_active', true)
            ->get()
            ->filter(function($hub) use ($userTier, $role) {
                if ($role === 'admin') {
                    return true;
                }
                $tiers = [
                    'free' => 0,
                    'community' => 1,
                    'preparation' => 2
                ];
                $userVal = $tiers[strtolower($userTier)] ?? 0;
                $reqVal = $tiers[strtolower($hub->access_level)] ?? 0;
                return $userVal >= $reqVal;
            })
            ->pluck('id')
            ->toArray();

        // 2. Get joined hub IDs based on membership
        $joinedHubIds = [];
        if ($userId) {
            $joinedHubIds = \DB::table('hub_members')
                ->where('user_id', $userId)
                ->pluck('hub_id')
                ->toArray();
        }

        if ($driver === 'pgsql') {
            $queryBuilder = \DB::table('community_threads')
                ->join('users', 'community_threads.user_id', '=', 'users.id')
                ->select(
                    'community_threads.id',
                    'community_threads.hub_id',
                    'community_threads.title',
                    'community_threads.content',
                    'users.name as author_name'
                )
                ->selectRaw("ts_rank(to_tsvector('english', coalesce(community_threads.title, '') || ' ' || coalesce(community_threads.content, '')), plainto_tsquery('english', ?)) as relevance", [$query])
                ->whereRaw("to_tsvector('english', coalesce(community_threads.title, '') || ' ' || coalesce(community_threads.content, '')) @@ plainto_tsquery('english', ?)", [$query])
                ->where('community_threads.is_active', true);
        } else {
            $queryBuilder = \DB::table('community_threads')
                ->join('users', 'community_threads.user_id', '=', 'users.id')
                ->select(
                    'community_threads.id',
                    'community_threads.hub_id',
                    'community_threads.title',
                    'community_threads.content',
                    'users.name as author_name'
                )
                ->where('community_threads.is_active', true)
                ->where(function($q) use ($query) {
                    $q->where('community_threads.title', 'like', "%{$query}%")
                      ->orWhere('community_threads.content', 'like', "%{$query}%");
                });
        }

        // Apply Moderation (Approval), Tier, and Membership checks
        if ($role !== 'admin') {
            $queryBuilder->whereIn('community_threads.hub_id', $accessibleHubIds)
                ->whereIn('community_threads.hub_id', $joinedHubIds)
                ->where(function($q) use ($userId) {
                    $q->where('community_threads.status', 'approved')
                      ->orWhere('community_threads.user_id', $userId);
                });
        }

        $threads = $queryBuilder->get();

        return collect($threads)->map(function($t) {
            return [
                'id' => $t->id,
                'hub_id' => $t->hub_id,
                'title' => $t->title,
                'excerpt' => mb_substr(strip_tags($t->content), 0, 120) . '...',
                'author' => $t->author_name,
            ];
        })->toArray();
    }

    /**
     * Search custodians using TSVECTOR (PostgreSQL) or fallback LIKE (MySQL/SQLite)
     */
    private function searchCustodians(string $query): array
    {
        $driver = \DB::connection()->getDriverName();
        $role = auth()->user() ? auth()->user()->role : 'customer';

        if ($driver === 'pgsql') {
            $queryBuilder = \DB::table('users')
                ->select('id', 'name', 'specialty', 'description', 'short_bio', 'location', 'country')
                ->where('role', 'custodian')
                ->selectRaw("ts_rank(to_tsvector('english', coalesce(name, '') || ' ' || coalesce(specialty, '') || ' ' || coalesce(description, '') || ' ' || coalesce(short_bio, '') || ' ' || coalesce(about, '')), plainto_tsquery('english', ?)) as relevance", [$query])
                ->whereRaw("to_tsvector('english', coalesce(name, '') || ' ' || coalesce(specialty, '') || ' ' || coalesce(description, '') || ' ' || coalesce(short_bio, '') || ' ' || coalesce(about, '')) @@ plainto_tsquery('english', ?)", [$query]);
        } else {
            $queryBuilder = \DB::table('users')
                ->select('id', 'name', 'specialty', 'description', 'short_bio', 'location', 'country')
                ->where('role', 'custodian')
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('specialty', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhere('short_bio', 'like', "%{$query}%")
                      ->orWhere('about', 'like', "%{$query}%")
                      ->orWhere('location', 'like', "%{$query}%")
                      ->orWhere('country', 'like', "%{$query}%");
                });
        }

        // Apply custodian approval/verification and active status check
        if ($role !== 'admin') {
            $queryBuilder->where('status', 'active')
                ->where('verified', true);
        } else {
            $queryBuilder->where(function($q) {
                $q->where('status', 'active')->orWhereNull('status');
            });
        }

        $custodians = $queryBuilder->get();

        return collect($custodians)->map(function($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'specialty' => $c->specialty,
                'short_bio' => $c->short_bio,
                'location' => ($c->location && $c->country) ? "{$c->location}, {$c->country}" : ($c->location ?: $c->country ?: 'Unknown'),
            ];
        })->toArray();
    }

    /**
     * Check tier access for search modules
     */
    private function hasTierAccess(string $userTier, int $stageId): bool
    {
        $requiredTier = 'free';
        if ($stageId === 2) {
            $requiredTier = 'community';
        } elseif ($stageId > 2) {
            $requiredTier = 'preparation';
        }

        $tiers = [
            'free' => 0,
            'community' => 1,
            'preparation' => 2
        ];

        $userVal = $tiers[strtolower($userTier)] ?? 0;
        $reqVal = $tiers[strtolower($requiredTier)] ?? 0;

        return $userVal >= $reqVal;
    }

    /**
     * Static modules list matching frontend configuration
     */
    private function getStaticModules(): array
    {
        return [
            ['id' => '1.1', 'moduleNumber' => 1, 'track' => 'Emotional Preparation', 'title' => 'Welcome — Your Journey Begins', 'slug' => 'welcome-your-journey-begins', 'type' => 'Story Module', 'body' => 'Welcome, relative. You are not a tourist. You are not a stranger. You are coming home.'],
            ['id' => '1.2', 'moduleNumber' => 2, 'track' => 'Emotional Preparation', 'title' => 'Ghana is Not Wakanda — Managing Expectations', 'slug' => 'ghana-is-not-wakanda-managing-expectations', 'type' => 'Story Module', 'body' => 'Wakanda does not exist. But the longing that Wakanda satisfies is real. Ghana is a middle-income African country with extraordinary cultural depth.'],
            ['id' => '1.3', 'moduleNumber' => 3, 'track' => 'Emotional Preparation', 'title' => 'The Uncomfortable Truths', 'slug' => 'the-uncomfortable-truths', 'type' => 'Story Module', 'body' => 'There are things that happen to diaspora visitors in Ghana that the tourism boards don\'t mention. Locals calling you obruni.'],
            ['id' => '1.4', 'moduleNumber' => 4, 'track' => 'Emotional Preparation', 'title' => 'Preparing for the Emotional Weight', 'slug' => 'preparing-for-the-emotional-weight', 'type' => 'Reflection Lab', 'body' => 'Preparing for a journey that will touch the deepest parts of your soul. Cape Coast Castle, the Door of No Return.'],
            ['id' => '1.5', 'moduleNumber' => 5, 'track' => 'Emotional Preparation', 'title' => 'Reflection & Stage 1 Quiz', 'slug' => 'reflection-stage-1-quiz', 'type' => 'Knowledge Quest', 'body' => 'You have completed Stage 1. Quiz to pass to Stage 2.'],
            
            ['id' => '2.1', 'moduleNumber' => 1, 'track' => 'Cultural Intelligence', 'title' => 'Greetings & the Art of Acknowledgment', 'slug' => 'greetings-the-art-of-acknowledgment', 'type' => 'Story Module', 'body' => 'Greetings and protocols for daily life in Ghana.'],
            ['id' => '2.2', 'moduleNumber' => 2, 'track' => 'Cultural Intelligence', 'title' => 'The Right-Hand Rule & Protocols', 'slug' => 'the-right-hand-rule-protocols', 'type' => 'Protocol Lab', 'body' => 'Right hand usage, gestures, and greeting protocols.'],
            ['id' => '2.3', 'moduleNumber' => 3, 'track' => 'Cultural Intelligence', 'title' => 'Elder Etiquette', 'slug' => 'elder-etiquette', 'type' => 'Story Module', 'body' => 'Respecting elders, greeting protocols, and family etiquette.'],
            ['id' => '2.4', 'moduleNumber' => 4, 'track' => 'Cultural Intelligence', 'title' => 'Market Language & Bargaining', 'slug' => 'market-language-bargaining', 'type' => 'Practical Guide', 'body' => 'Twi market bargaining phrases, prices, and protocols.'],
            ['id' => '2.5', 'moduleNumber' => 5, 'track' => 'Cultural Intelligence', 'title' => 'Food, Taboos & Table Culture', 'slug' => 'food-taboos-table-culture', 'type' => 'Story Module', 'body' => 'Ghanaian food culture, table manners, hand-washing protocols.'],
            ['id' => '2.6', 'moduleNumber' => 6, 'track' => 'Cultural Intelligence', 'title' => 'Dress, Modesty & Sacred Spaces', 'slug' => 'dress-modesty-sacred-spaces', 'type' => 'Story Module', 'body' => 'Dress codes, traditional wear, modest clothing in shrines and churches.'],
            ['id' => '2.7', 'moduleNumber' => 7, 'track' => 'Cultural Intelligence', 'title' => 'Pop Quiz Cards — Cultural Protocols', 'slug' => 'pop-quiz-cards-cultural-protocols', 'type' => 'Knowledge Quest', 'body' => 'Flashcards for daily protocols.'],
            ['id' => '2.8', 'moduleNumber' => 8, 'track' => 'Cultural Intelligence', 'title' => 'Stage 2 Quiz', 'slug' => 'stage-2-quiz', 'type' => 'Knowledge Quest', 'body' => 'Stage 2 final protocol quiz.'],

            ['id' => '3.1', 'moduleNumber' => 1, 'track' => 'Practical Preparation', 'title' => 'Visa & Documentation', 'slug' => 'visa-documentation', 'type' => 'Practical Guide', 'body' => 'Visa application requirements, dynamic forms, and checklist.'],
            ['id' => '3.2', 'moduleNumber' => 2, 'track' => 'Practical Preparation', 'title' => 'Health Preparation & Vaccinations', 'slug' => 'health-preparation-vaccinations', 'type' => 'Practical Guide', 'body' => 'Yellow fever vaccination, malaria prophylaxis, and medical supplies.'],
            ['id' => '3.3', 'moduleNumber' => 3, 'track' => 'Practical Preparation', 'title' => 'Packing for Ghana', 'slug' => 'packing-for-ghana', 'type' => 'Practical Guide', 'body' => 'What to pack: lightweight clothing, adapters, specific medications.'],
            ['id' => '3.4', 'moduleNumber' => 4, 'track' => 'Practical Preparation', 'title' => 'Money & Budgeting', 'slug' => 'money-budgeting', 'type' => 'Practical Guide', 'body' => 'ATMs, local currency (Cedis), mobile money (Momo), exchange rates.'],
            ['id' => '3.5', 'moduleNumber' => 5, 'track' => 'Practical Preparation', 'title' => 'Transport & Getting Around', 'slug' => 'transport-getting-around', 'type' => 'Practical Guide', 'body' => 'Uber, Bolt, tro-tros, domestic flights, and taxi protocols.'],
            ['id' => '3.6', 'moduleNumber' => 6, 'track' => 'Practical Preparation', 'title' => 'Stage 3 Quiz', 'slug' => 'stage-3-quiz', 'type' => 'Knowledge Quest', 'body' => 'Stage 3 final practical quiz.'],

            ['id' => '4.1', 'moduleNumber' => 1, 'track' => 'Arrival Orientation', 'title' => 'The Airport & First Hours', 'slug' => 'the-airport-first-hours', 'type' => 'Orientation Lab', 'body' => 'Landing at Kotoka, customs, baggage claim, airport pickup.'],
            ['id' => '4.2', 'moduleNumber' => 2, 'track' => 'Arrival Orientation', 'title' => 'Your Host & First Night', 'type' => 'Orientation Lab', 'body' => 'Meeting your host or driver, check-in, orientation.'],
            ['id' => '4.3', 'moduleNumber' => 3, 'track' => 'Arrival Orientation', 'title' => "Day Name & Chief's Blessing", 'type' => 'Story Module', 'body' => 'Receiving your Ghanaian day name (Kofi, Ama, etc.) and royal protocols.'],
            ['id' => '4.4', 'moduleNumber' => 4, 'track' => 'Arrival Orientation', 'title' => 'Jet-Lag & Emotional Reset', 'type' => 'Reflection Lab', 'body' => 'A physical and emotional landing meditation.'],
            ['id' => '4.5', 'moduleNumber' => 5, 'track' => 'Arrival Orientation', 'title' => 'Safety, Health & Getting Your Bearings', 'type' => 'Practical Guide', 'body' => 'Emergency numbers, drinking water, local map setups.'],
            ['id' => '4.6', 'moduleNumber' => 6, 'track' => 'Arrival Orientation', 'title' => 'Stage 4 Quiz', 'type' => 'Knowledge Quest', 'body' => 'Stage 4 arrival orientation quiz.'],

            ['id' => '5.1', 'moduleNumber' => 1, 'track' => 'Heritage Journey Experience', 'title' => 'The Weight of Sacred Ground', 'type' => 'Reflection Lab', 'body' => 'Mental preparation for slave dungeons and heritage sites.'],
            ['id' => '5.2', 'moduleNumber' => 2, 'track' => 'Heritage Journey Experience', 'title' => 'Cape Coast Castle — History & Meaning', 'type' => 'Story Module', 'body' => 'Historical background of Cape Coast Castle and trans-Atlantic slave trade.'],
            ['id' => '5.3', 'moduleNumber' => 3, 'track' => 'Heritage Journey Experience', 'title' => 'The Door of No Return', 'type' => 'Reflection Lab', 'body' => 'Walking through the Door of No Return and the Door of Return.'],
            ['id' => '5.4', 'moduleNumber' => 4, 'track' => 'Heritage Journey Experience', 'title' => 'Identity Tension & the Hyphenated Self', 'type' => 'Story Module', 'body' => 'Navigating being perceived as obruni or a local.'],
            ['id' => '5.5', 'moduleNumber' => 5, 'track' => 'Heritage Journey Experience', 'title' => 'Sensory Overload & Self-Care', 'type' => 'Reflection Lab', 'body' => 'Sensory management and physical grounding during intense visits.'],
            ['id' => '5.6', 'moduleNumber' => 6, 'track' => 'Heritage Journey Experience', 'title' => 'Connecting with Living Ancestors', 'type' => 'Story Module', 'body' => 'Elders, protocols, traditional shrines, and ancestral roots.'],
            ['id' => '5.7', 'moduleNumber' => 7, 'track' => 'Heritage Journey Experience', 'title' => 'Stage 5 Reflection', 'type' => 'Knowledge Quest', 'body' => 'Stage 5 final heritage quiz.'],

            ['id' => '6.1', 'moduleNumber' => 1, 'track' => 'Post-Journey Integration', 'title' => 'Reverse Culture Shock', 'type' => 'Integration Lab', 'body' => 'Re-entry processing when returning to your home country.'],
            ['id' => '6.2', 'moduleNumber' => 2, 'track' => 'Post-Journey Integration', 'title' => 'From Reflection to Habit', 'type' => 'Reflection Lab', 'body' => 'Integrating learnings into daily life routines.'],
            ['id' => '6.3', 'moduleNumber' => 3, 'track' => 'Post-Journey Integration', 'title' => 'Reframing the Narrative', 'type' => 'Story Module', 'body' => 'How to tell your return story to friends and family.'],
            ['id' => '6.4', 'moduleNumber' => 4, 'track' => 'Post-Journey Integration', 'title' => 'Well-Being & Eudaimonic Growth', 'type' => 'Reflection Lab', 'body' => 'Long term emotional growth and healing.'],
            ['id' => '6.5', 'moduleNumber' => 5, 'track' => 'Post-Journey Integration', 'title' => 'Commitment to Action — Sankofa Pledge', 'type' => 'Knowledge Quest', 'body' => 'Sankofa pledge for community action.'],
        ];
    }
}
