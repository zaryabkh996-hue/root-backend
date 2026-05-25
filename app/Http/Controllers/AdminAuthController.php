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
        \Log::info('🔐 [ADMIN_AUTH] Login attempt started', [
            'email' => $request->input('email')
        ]);

        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            // Find user by email
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                \Log::warning('⚠️ [ADMIN_AUTH] User not found', [
                    'email' => $validated['email']
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password.'
                ], 401);
            }

            \Log::info('👤 [ADMIN_AUTH] User found', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);

            // Check if user has admin or custodian role
            if (!in_array($user->role, ['admin', 'custodian'])) {
                \Log::warning('❌ [ADMIN_AUTH] Unauthorized role', [
                    'user_id' => $user->id,
                    'role' => $user->role
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have admin access.'
                ], 403);
            }

            // Verify password
            if (!Hash::check($validated['password'], $user->password)) {
                \Log::warning('❌ [ADMIN_AUTH] Invalid password', [
                    'user_id' => $user->id,
                    'email' => $validated['email']
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password.'
                ], 401);
            }

            \Log::info('✅ [ADMIN_AUTH] Password verified', [
                'user_id' => $user->id
            ]);

            // Create API token
            $token = $user->createToken('admin-token')->plainTextToken;

            \Log::info('🔑 [ADMIN_AUTH] Admin token created', [
                'user_id' => $user->id,
                'role' => $user->role
            ]);

            \Log::info('✅ [ADMIN_AUTH] Admin login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);

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
                'message' => 'An error occurred during login. Please try again.'
            ], 500);
        }
    }

    /**
     * Admin logout
     */
    public function logout(Request $request)
    {
        \Log::info('🔓 [ADMIN_AUTH] Logout started', [
            'user_id' => $request->user()?->id
        ]);

        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
            \Log::info('✅ [ADMIN_AUTH] Logout successful', [
                'user_id' => $request->user()->id
            ]);
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

        if (!$user || !in_array($user->role, ['admin', 'custodian'])) {
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
