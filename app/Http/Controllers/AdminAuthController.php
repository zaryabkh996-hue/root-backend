<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    /**
     * Admin login with email and password
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            // Find user by email
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password.'
                ], 401);
            }

            // Check if user has admin 
            if (!in_array($user->role, ['admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have admin access.'
                ], 403);
            }

            // Verify password using Hash::check
            if (!Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password.'
                ], 401);
            }
            // Create API token
            $token = $user->createToken('admin-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ],
                'token' => $token
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ [ADMIN_AUTH] Login failed', [
                'email' => $validated['email'] ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin logout
     */
    public function logout(Request $request)
    {


        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated admin user
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, ['admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);
    }
}
