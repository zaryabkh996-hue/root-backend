<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProgress;
use App\Models\JourneyPhoto;
use App\Models\Story;
use App\Models\CommunityThread;
use App\Models\CommunityHub;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Get user profile
     */
    public function show(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get user progress for journey info
            $progress = UserProgress::where('user_id', $user->id)->first();

            // Get the base URL for storage
            $storageUrl = rtrim(env('APP_URL', 'http://localhost'), '/') . '/storage';

            // Helper function to construct complete URL from relative path
            $getCompleteUrl = function($path) use ($storageUrl) {
                if (!$path) return null;
                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                    return $path;
                }
                return $storageUrl . '/' . ltrim($path, '/');
            };

            // Prepare profile data
            $defaultNotifications = [
                'stageTransitions' => true,
                'preTripCheckIns' => true,
                'communityDigest' => true,
                'newCustodians' => false,
            ];
            $notifications = array_merge($defaultNotifications, $user->notification_preferences ?? []);

            $profileData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'whatsapp' => $user->whatsapp,
                'subscriptionTier' => $user->subscription_tier,
                'picture' => $getCompleteUrl($user->picture),
                'bio' => $user->bio,
                'bioPrivacy' => $user->bio_privacy,
                'travelDate' => $user->travel_date,
                'travelLocation' => $user->travel_location,
                'diasporaGroup' => $user->diaspora_group,
                'learningPreference' => $user->learning_preference,
                'profileVisibility' => $user->profile_visibility,
                'journeyPhotosDefault' => $user->journey_photos_default,
                'showScorePublicly' => $user->show_score_publicly,
                'notificationPreferences' => $notifications,
                'memberSince' => $user->created_at ? $user->created_at->format('d M Y') : null,
                'certification' => $user->certification,
            ];

            // Add progress info if exists
            if ($progress) {
                $profileData['afroScore'] = $progress->afro_score ?? 0;
                $profileData['userPersona'] = $progress->user_persona;
                $profileData['lifecyclePhase'] = $progress->lifecycle_phase;
                $profileData['completedStages'] = $progress->completed_stages ?? [];
            }

            return response()->json([
                'success' => true,
                'data' => $profileData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function update(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'whatsapp' => 'sometimes|string|max:20|nullable',
                'bio' => 'sometimes|string|max:280|nullable',
                'bioPrivacy' => 'sometimes|in:public,community,private',
                'travelDate' => 'sometimes|string|nullable',
                'travelLocation' => 'sometimes|string|max:255|nullable',
                'diasporaGroup' => 'sometimes|string|max:255|nullable',
                'learningPreference' => 'sometimes|string|max:255|nullable',
                'profileVisibility' => 'sometimes|in:public,community,private',
                'journeyPhotosDefault' => 'sometimes|in:public,community,private',
                'showScorePublicly' => 'sometimes|boolean',
                'certification' => 'sometimes|string|nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Map camelCase to snake_case for database
            $dataToUpdate = [];
            
            if ($request->has('name')) {
                $dataToUpdate['name'] = $request->input('name');
            }
            if ($request->has('email')) {
                $dataToUpdate['email'] = $request->input('email');
            }
            if ($request->has('whatsapp')) {
                $dataToUpdate['whatsapp'] = $request->input('whatsapp');
            }
            if ($request->has('bio')) {
                $dataToUpdate['bio'] = $request->input('bio');
            }
            if ($request->has('bioPrivacy')) {
                $dataToUpdate['bio_privacy'] = $request->input('bioPrivacy');
            }
            if ($request->has('travelDate')) {
                $dataToUpdate['travel_date'] = $request->input('travelDate');
            }
            if ($request->has('travelLocation')) {
                $dataToUpdate['travel_location'] = $request->input('travelLocation');
            }
            if ($request->has('diasporaGroup')) {
                $dataToUpdate['diaspora_group'] = $request->input('diasporaGroup');
            }
            if ($request->has('learningPreference')) {
                $dataToUpdate['learning_preference'] = $request->input('learningPreference');
            }
            if ($request->has('profileVisibility')) {
                $dataToUpdate['profile_visibility'] = $request->input('profileVisibility');
            }
            if ($request->has('journeyPhotosDefault')) {
                $dataToUpdate['journey_photos_default'] = $request->input('journeyPhotosDefault');
            }
            if ($request->has('showScorePublicly')) {
                $dataToUpdate['show_score_publicly'] = $request->input('showScorePublicly');
            }
            if ($request->has('certification')) {
                $dataToUpdate['certification'] = $request->input('certification');
            }

            // Update user
            $user->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'whatsapp' => $user->whatsapp,
                    'subscriptionTier' => $user->subscription_tier,
                    'bio' => $user->bio,
                    'bioPrivacy' => $user->bio_privacy,
                    'travelDate' => $user->travel_date,
                    'travelLocation' => $user->travel_location,
                    'diasporaGroup' => $user->diaspora_group,
                    'learningPreference' => $user->learning_preference,
                    'profileVisibility' => $user->profile_visibility,
                    'journeyPhotosDefault' => $user->journey_photos_default,
                    'showScorePublicly' => $user->show_score_publicly,
                    'certification' => $user->certification,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Grant Returned Traveller role to the authenticated user
     */
    public function grantReturnedTraveller(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $user->is_returned_traveller = true;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Returned Traveller role granted successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to grant role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user notifications preferences
     */
    public function updateNotifications(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'stageTransitions' => 'sometimes|boolean',
                'preTripCheckIns' => 'sometimes|boolean',
                'communityDigest' => 'sometimes|boolean',
                'newCustodians' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $currentPrefs = $user->notification_preferences ?? [
                'stageTransitions' => true,
                'preTripCheckIns' => true,
                'communityDigest' => true,
                'newCustodians' => false,
            ];

            // Merge with request inputs
            foreach (['stageTransitions', 'preTripCheckIns', 'communityDigest', 'newCustodians'] as $key) {
                if ($request->has($key)) {
                    $currentPrefs[$key] = $request->boolean($key);
                }
            }

            $user->update([
                'notification_preferences' => $currentPrefs
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated',
                'data' => $currentPrefs,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload user profile picture
     */
    public function uploadPicture(Request $request)
    {
        try {
            \Log::info('[ProfileController] uploadPicture - Request received', [
                'user_id' => $request->user()?->id,
                'has_picture' => $request->hasFile('picture'),
            ]);

            $user = $request->user();

            if (!$user) {
                \Log::warning('[ProfileController] uploadPicture - No authenticated user');
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Check if client passed a pre-uploaded Cloudinary URL
            if ($request->has('picture_url')) {
                $pictureUrl = $request->input('picture_url');
                $user->update(['picture' => $pictureUrl]);
                return response()->json([
                    'success' => true,
                    'message' => 'Picture updated successfully',
                    'data' => [
                        'picture' => $pictureUrl,
                    ],
                ], 200);
            }

            // Validate file
            $validator = Validator::make($request->all(), [
                'picture' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // max 5MB
            ]);

            if ($validator->fails()) {
                \Log::warning('[ProfileController] uploadPicture - Validation failed', [
                    'errors' => $validator->errors()->toArray(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Delete old picture if exists
            if ($user->picture) {
                \Log::info('[ProfileController] uploadPicture - Attempting to delete old picture', [
                    'old_picture' => $user->picture,
                ]);
                // Old picture is stored as relative path
                if (Storage::disk('public')->exists($user->picture)) {
                    Storage::disk('public')->delete($user->picture);
                    \Log::info('[ProfileController] uploadPicture - Old picture deleted');
                }
            }

            // Store new picture
            $file = $request->file('picture');
            \Log::info('[ProfileController] uploadPicture - Storing new file', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);

            $path = $file->store('profiles', 'public');
            $relativePath = 'profiles/' . basename($path);
            \Log::info('[ProfileController] uploadPicture - File stored', [
                'path' => $path,
                'relative_path' => $relativePath,
            ]);

            // Update user with relative path only
            $user->update(['picture' => $relativePath]);
            \Log::info('[ProfileController] uploadPicture - User updated in database', [
                'picture' => $relativePath,
            ]);

            // Get the base URL for storage
            $storageUrl = rtrim(env('APP_URL', 'http://localhost'), '/') . '/storage';
            $completeUrl = $storageUrl . '/' . $relativePath;
            \Log::info('[ProfileController] uploadPicture - URL constructed', [
                'storage_url' => $storageUrl,
                'complete_url' => $completeUrl,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Picture uploaded successfully',
                'data' => [
                    'picture' => $completeUrl,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[ProfileController] uploadPicture - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload picture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user journey photos
     */
    public function getJourneyPhotos(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Get the base URL for storage
            $storageUrl = rtrim(env('APP_URL', 'http://localhost'), '/') . '/storage';

            $photos = JourneyPhoto::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($photo) use ($storageUrl) {
                    // Construct complete URL from relative path stored in DB
                    $completeUrl = (str_starts_with($photo->url, 'http://') || str_starts_with($photo->url, 'https://'))
                        ? $photo->url
                        : $storageUrl . '/' . ltrim($photo->url, '/');
                    
                    return [
                        'id' => $photo->id,
                        'url' => $completeUrl,
                        'caption' => $photo->caption,
                        'hub' => $photo->hub,
                        'visibility' => $photo->visibility,
                        'createdAt' => $photo->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $photos,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[ProfileController] getJourneyPhotos - Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch journey photos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload journey photo
     */
    public function uploadJourneyPhoto(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Check if client passed a pre-uploaded Cloudinary URL
            if ($request->has('photo_url')) {
                $journeyPhoto = JourneyPhoto::create([
                    'user_id' => $user->id,
                    'url' => $request->input('photo_url'),
                    'caption' => $request->input('caption', ''),
                    'hub' => $request->input('hub', 'Love Hub'),
                    'visibility' => $request->input('visibility', $user->journey_photos_default ?? 'community'),
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Photo added successfully',
                    'data' => [
                        'id' => $journeyPhoto->id,
                        'url' => $journeyPhoto->url,
                        'caption' => $journeyPhoto->caption,
                        'hub' => $journeyPhoto->hub,
                        'visibility' => $journeyPhoto->visibility,
                        'createdAt' => $journeyPhoto->created_at->format('Y-m-d H:i:s'),
                    ],
                ], 200);
            }

            // Validate file
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // max 10MB
                'caption' => 'sometimes|string|max:255|nullable',
                'hub' => 'sometimes|string|max:255',
                'visibility' => 'sometimes|in:public,community,private',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Store photo
            $file = $request->file('photo');
            $path = $file->store('journey-photos', 'public');
            $relativePath = 'journey-photos/' . basename($path);

            // Create journey photo record
            $journeyPhoto = JourneyPhoto::create([
                'user_id' => $user->id,
                'url' => $relativePath,
                'caption' => $request->input('caption', ''),
                'hub' => $request->input('hub', 'Love Hub'),
                'visibility' => $request->input('visibility', $user->journey_photos_default ?? 'community'),
            ]);

            // Get the base URL for storage
            $storageUrl = rtrim(env('APP_URL', 'http://localhost'), '/') . '/storage';
            $completeUrl = $storageUrl . '/' . $relativePath;

            \Log::info('[ProfileController] uploadJourneyPhoto - URL constructed', [
                'relative_path' => $relativePath,
                'storage_url' => $storageUrl,
                'complete_url' => $completeUrl,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'data' => [
                    'id' => $journeyPhoto->id,
                    'url' => $completeUrl,
                    'caption' => $journeyPhoto->caption,
                    'hub' => $journeyPhoto->hub,
                    'visibility' => $journeyPhoto->visibility,
                    'createdAt' => $journeyPhoto->created_at->format('Y-m-d H:i:s'),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List customer's own stories
     */
    public function listStories(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $stories = Story::where('author_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $stories
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all approved stories (public/library)
     */
    public function getApprovedStories(Request $request)
    {
        try {
            $stories = Story::where('status', 'approved')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $stories
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show single story details
     */
    public function showStory(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $story = Story::findOrFail($id);

            // Allow only author or admin to view non-approved story details
            if ($story->status !== 'approved' && $story->author_id !== $user->id && $user->role !== 'admin') {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $story
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new story draft & grant Returned Traveller role
     */
    public function storeStory(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'body' => 'required|string',
                'sanity_id' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            // Create story locally
            $story = Story::create([
                'sanity_id' => $request->input('sanity_id'),
                'title' => $request->input('title'),
                'body' => $request->input('body'),
                'author' => $user->name,
                'author_id' => $user->id,
                'status' => 'pending',
            ]);

            // Automatically grant Returned Traveller role
            if (!$user->is_returned_traveller) {
                $user->is_returned_traveller = true;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Story draft created and Returned Traveller status granted.',
                'data' => $story
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update/resubmit a story draft
     */
    public function updateStory(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $story = Story::findOrFail($id);

            // Author ownership check
            if ($story->author_id !== $user->id && $user->role !== 'admin') {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'body' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $story->update([
                'title' => $request->input('title'),
                'body' => $request->input('body'),
                'status' => 'pending', // reset to pending
                'revision_note' => null, // clear revision note
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Story updated and resubmitted successfully',
                'data' => $story
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Admin: Get all pending stories
     */
    public function getPendingStories(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user || $user->role !== 'admin') {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $stories = Story::where('status', 'pending')
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $stories
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Admin: Approve story, set hub routing, create community thread
     */
    public function approveStory(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user || $user->role !== 'admin') {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $story = Story::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'hub_id' => 'required|integer|exists:community_hubs,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $hubId = $request->input('hub_id');

            // Update story database state
            $story->update([
                'status' => 'approved',
                'community_hub_id' => $hubId,
                'revision_note' => null,
            ]);

            // Fetch author details to enrich the thread info
            $author = User::find($story->author_id);
            $authorProgress = UserProgress::where('user_id', $story->author_id)->first();

            // Create community thread on behalf of the story author
            $thread = CommunityThread::create([
                'hub_id' => $hubId,
                'user_id' => $story->author_id,
                'title' => $story->title,
                'content' => $story->body,
                'status' => 'approved', // instantly approved thread
                'location' => $author ? ($author->location ?? $author->country) : null,
                'user_stage' => $authorProgress && $authorProgress->current_module_id ? 'Stage ' . explode('.', $authorProgress->current_module_id)[0] : 'Stage 1',
                'user_tier' => $author ? ucfirst($author->subscription_tier) : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Story approved and routed to community hub thread.',
                'data' => $story
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Admin: Request story revision with comment
     */
    public function rejectStory(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user || $user->role !== 'admin') {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }

            $story = Story::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'revision_note' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $story->update([
                'status' => 'revision',
                'revision_note' => $request->input('revision_note'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Story revision request submitted successfully.',
                'data' => $story
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
