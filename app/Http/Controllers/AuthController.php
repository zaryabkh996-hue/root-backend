<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Pail\ValueObjects\Origin\Console;

class AuthController extends Controller
{
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
                'picture' => $user->picture ?? null,
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

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        $token = Str::random(64);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * Register or update user from OAuth (Google, Apple, etc.)
     */
    public function registerOAuth(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'name' => 'required|string|max:255',
            'auth0_id' => 'required|string|max:255',
            'picture' => 'nullable|string',
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
            // Check if user already exists by email
            $user = User::where('email', $request->email)->first();
            
            

            if ($user) {
                // User exists - update their Auth0 ID if different
                if (!$user->auth0_id || $user->auth0_id !== $request->auth0_id) {
                    
                    $user->update([
                        'auth0_id' => $request->auth0_id,
                        'name' => $request->name,
                        'picture' => $request->picture,
                    ]);
                    
                }
            } else {
                // Create new user from OAuth
                
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'auth0_id' => $request->auth0_id,
                    'picture' => $request->picture,
               'password' => Hash::make(\Str::random(32)), // Random password since using magic link
                    'provider' => $request->provider || 'auth0',
                    'email_verified_at' => now(), // Email is verified by Auth0
                ]);
                
            }

            // Revoke old tokens, issue a fresh Sanctum token
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
                        'email' => $user->email,
                        'picture' => $user->picture,
                        'auth0_id' => $user->auth0_id,
                        'role' => $user->role,
                    ],
                ],
            ], $user->wasRecentlyCreated ? 201 : 200);

        } catch (\Exception $e) {
            

            return response()->json([
                'success' => false,
                'message' => 'OAuth registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save quiz data for authenticated user (used after OAuth registration)
     */
    public function saveQuizData(Request $request)
    {
        $validator = Validator::make($request->all(), [
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
            // Get authenticated user from token (set by middleware)
            // For now, we'll extract from the authorization header or from request
            $authHeader = $request->header('Authorization');
            $token = str_replace('Bearer ', '', $authHeader ?? '');

            // If no token in header, user might be identified from session
            // For this implementation, we expect the frontend to pass user context
            $userId = $request->input('user_id');
            
            if (!$userId && !$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Find user by ID
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Save quiz data
            if ($request->has('quiz_data') && $request->input('quiz_data')) {
                $user->update([
                    'quiz_data' => $request->input('quiz_data')
                ]);
            }

            

            return response()->json([
                'success' => true,
                'message' => 'Quiz data saved successfully',
                'data' => [
                    'user_id' => $user->id,
                    'quiz_data_saved' => true
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
}
