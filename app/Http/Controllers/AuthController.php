<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use App\Services\Auth0TokenVerifier;

class AuthController extends Controller
{
    public function __construct(
        private Auth0TokenVerifier $tokenVerifier
    ) {}
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Revoke old tokens, issue a fresh Sanctum token
            $user->tokens()->delete();
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'subscription_tier' => $user->subscription_tier,
                        'onboarded' => (bool)$user->onboarded,
                        'learning_preference' => $user->learning_preference,
                        'travel_date' => $user->travel_date,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'error' => 'The provided credentials are incorrect',
            ], 401);
        }

        // Revoke old tokens, issue a fresh Sanctum token
        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'subscription_tier' => $user->subscription_tier,
                    'onboarded' => (bool)$user->onboarded,
                    'learning_preference' => $user->learning_preference,
                    'travel_date' => $user->travel_date,
                    'quiz_data' => $user->quiz_data,
                    'is_returned_traveller' => (bool)$user->is_returned_traveller,
                ],
            ],
        ], 200);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'      => $user->id,
                'name'    => $user->name,
                'email'   => $user->email,
                'role'    => $user->role,
                'subscription_tier' => $user->subscription_tier,
                'picture' => $user->picture ?? null,
                'onboarded' => (bool)$user->onboarded,
                'learning_preference' => $user->learning_preference,
                'travel_date' => $user->travel_date,
                'quiz_data' => $user->quiz_data,
                'is_returned_traveller' => (bool)$user->is_returned_traveller,
            ],
        ], 200);
    }

    /**
     * Logout user — revoke current token
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        
        if ($user) {
            // Revoke current token
            $user->currentAccessToken()->delete();
            
            // Create a new token
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                ],
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated',
        ], 401);
    }

    public function registerOAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
            'provider' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Cryptographically verify Auth0 ID token
            $payload = $this->tokenVerifier->verify($request->id_token);
            $auth0Id = $payload['sub'] ?? null;
            $email = $payload['email'] ?? null;
            $name = $payload['name'] ?? 'User';
            $picture = $payload['picture'] ?? null;

            if (!$auth0Id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token payload: missing sub claim.',
                ], 400);
            }

            // Find user ONLY by auth0_id to prevent account spoofing
            $user = User::where('auth0_id', $auth0Id)->first();

            if (!$user && $email) {
                // Link existing user if found by verified email
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->update([
                        'auth0_id' => $auth0Id,
                        'picture' => $picture ?: $user->picture,
                    ]);
                }
            }

            if (!$user) {
                // Create a new user
                $user = User::forceCreate([
                    'name' => $name,
                    'email' => $email,
                    'role' => 'customer',
                    'auth0_id' => $auth0Id,
                    'picture' => $picture,
                    'password' => Hash::make(Str::random(32)),
                    'provider' => $request->provider ?? 'auth0',
                    'email_verified_at' => now(),
                ]);
            }

            // Revoke old tokens after identity validation/change to prevent token leakage
            $user->tokens()->delete();
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => $user->wasRecentlyCreated ? 'User registered via OAuth' : 'User updated successfully',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'email' => $user->email,
                        'subscription_tier' => $user->subscription_tier,
                        'picture' => $user->picture,
                        'auth0_id' => $user->auth0_id,
                        'onboarded' => (bool)$user->onboarded,
                        'learning_preference' => $user->learning_preference,
                        'travel_date' => $user->travel_date,
                        'quiz_data' => $user->quiz_data,
                        'is_new_user' => (bool)$user->wasRecentlyCreated,
                    ],
                ],
            ], $user->wasRecentlyCreated ? 201 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'OAuth authentication failed: ' . $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Save quiz data for authenticated user (used after OAuth registration)
     */
    public function saveQuizData(Request $request)
    {
        $validated = $request->validate([
            'quiz_data' => 'nullable|array',
            'quiz_token' => 'nullable|string'
        ]);

        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Save quiz data
            $quizData = $request->input('quiz_data');
            if ($request->filled('quiz_token')) {
                $resolved = \Illuminate\Support\Facades\Cache::get("quiz:{$request->input('quiz_token')}");
                if ($resolved) {
                    $quizData = $resolved;
                }
            }

            if ($quizData) {
                $learningPref = $quizData['onboardingAnswers']['whatBroughtYouHere'] ?? null;
                $travelDate = $quizData['onboardingAnswers']['travelTimeline'] ?? null;

                $updateData = [
                    'quiz_data' => $quizData
                ];

                if (!empty($learningPref) && !empty($travelDate)) {
                    $updateData['learning_preference'] = $learningPref;
                    $updateData['travel_date'] = $travelDate;
                    $updateData['onboarded'] = true;
                }

                $user->update($updateData);

                // Update UserProgress
                $progress = \App\Models\UserProgress::firstOrNew(['user_id' => $user->id]);
                $progress->afro_score = $quizData['totalScore'] ?? 0;
                $progress->user_persona = $quizData['persona'] ?? 'Heritage Seeker';
                $progress->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Quiz data saved successfully',
                'data' => [
                    'user_id' => $user->id,
                    'quiz_data_saved' => true,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'subscription_tier' => $user->subscription_tier,
                        'onboarded' => (bool)$user->onboarded,
                        'learning_preference' => $user->learning_preference,
                        'travel_date' => $user->travel_date,
                        'quiz_data' => $user->quiz_data,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save quiz data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save onboarding answers and mark user as onboarded
     */
    public function saveOnboarding(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'learning_preference' => 'required|string|max:255',
            'travel_date' => 'required|string|max:255',
            'quiz_data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $updateData = [
                'learning_preference' => $request->input('learning_preference'),
                'travel_date' => $request->input('travel_date'),
                'onboarded' => true,
            ];

            if ($request->has('quiz_data')) {
                $updateData['quiz_data'] = $request->input('quiz_data');
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Onboarding complete',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'subscription_tier' => $user->subscription_tier,
                        'onboarded' => (bool)$user->onboarded,
                        'learning_preference' => $user->learning_preference,
                        'travel_date' => $user->travel_date,
                        'quiz_data' => $user->quiz_data,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save onboarding data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
