<?php

namespace App\Http\Controllers;

use App\Models\MagicLink;
use App\Models\User;
use App\Mail\MagicLinkEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class MagicLinkController extends Controller
{
    /**
     * Send magic link registration email
     */
    public function sendMagicLink(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'required|string|min:2',
            'whatsapp' => 'nullable|string',
            'quiz_data' => 'nullable|array'
        ]);

        try {
            // Check if user already exists
            if (User::where('email', $validated['email'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An account with this email already exists. Please sign in instead.'
                ], 409);
            }

            // Revoke any existing unused magic links for this email
            MagicLink::where('email', $validated['email'])->delete();

            // Create new magic link
            $token = MagicLink::generateToken();
            $magicLink = MagicLink::create([
                'email' => $validated['email'],
                'token' => $token,
                'name' => $validated['name'],
                'whatsapp' => $validated['whatsapp'] ?? null,
                'quiz_data' => $validated['quiz_data'] ?? null,
                'expires_at' => now()->addMinutes(15)
            ]);

            // Log magic link creation
            

            // Build verification URL (frontend URL)
            $frontendUrl = config('app.frontend_url') ?? env('APP_FRONTEND_URL', 'http://localhost:3000');
            $magicUrl = "{$frontendUrl}/auth/verify-magic-link?token={$token}";

            // Log email sending attempt
            

            // Send email with error handling
            try {
                // Use Mail::to()->send() for proper Mailable delivery
                Mail::to($validated['email'])->send(new MagicLinkEmail($magicLink, $magicUrl));
                
            } catch (\Exception $emailError) {
                
                throw $emailError;
            }

            return response()->json([
                'success' => true,
                'message' => 'Magic link sent! Check your email (it expires in 15 minutes).'
            ]);

        } catch (\Exception $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send magic link. Please try again.'
            ], 500);
        }
    }

    /**
     * Send magic link for sign-in (for existing users)
     */
    public function signInMagicLink(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            // Check if user exists
            $user = User::where('email', $validated['email'])->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this email. Please register first.'
                ], 404);
            }

            // Revoke any existing unused magic links for this email
            MagicLink::where('email', $validated['email'])->delete();

            // Create new magic link
            $token = MagicLink::generateToken();
            $magicLink = MagicLink::create([
                'email' => $validated['email'],
                'token' => $token,
                'name' => $user->name,
                'whatsapp' => $user->whatsapp,
                'quiz_data' => $user->quiz_data,
                'expires_at' => now()->addMinutes(15)
            ]);

            // Log magic link creation
            

            // Build verification URL (frontend URL)
            $frontendUrl = config('app.frontend_url') ?? env('APP_FRONTEND_URL', 'http://localhost:3000');
            $magicUrl = "{$frontendUrl}/auth/verify-magic-link?token={$token}";

            // Log email sending attempt
            

            // Send email with error handling
            try {
                Mail::to($validated['email'])->send(new MagicLinkEmail($magicLink, $magicUrl));
                
            } catch (\Exception $emailError) {
                
                throw $emailError;
            }

            return response()->json([
                'success' => true,
                'message' => 'Magic link sent! Check your email (it expires in 15 minutes).'
            ]);

        } catch (\Exception $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send magic link. Please try again.'
            ], 500);
        }
    }

    /**
     * Verify magic link and create user or sign in
     */
    public function verifyMagicLink(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string'
        ]);

        try {
            $magicLink = MagicLink::where('token', $validated['token'])->first();

            // Check if token exists
            if (!$magicLink) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid magic link.'
                ], 404);
            }

            // Check if already used
            if ($magicLink->used) {
                return response()->json([
                    'success' => false,
                    'message' => 'This magic link has already been used.'
                ], 409);
            }

            // Check if expired
            if (!$magicLink->isValid()) {
                $magicLink->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'This magic link has expired. Please register again.'
                ], 410);
            }

            // Check if user already exists (sign-in) or create new user (registration)
            $user = User::where('email', $magicLink->email)->first();

            if (!$user) {
                // Registration: Create new user
                $user = User::create([
                    'name' => $magicLink->name,
                    'email' => $magicLink->email,
                    'whatsapp' => $magicLink->whatsapp,
                    'password' => Hash::make(\Str::random(32)), // Random password since using magic link
                    'email_verified_at' => now(),
                ]);

                // Store quiz data if provided
                if ($magicLink->quiz_data) {
                    $user->update([
                        'quiz_data' => $magicLink->quiz_data
                    ]);
                }

                
            } else {
                // Sign-in: User already exists
                
            }

            // Mark magic link as used
            $magicLink->markAsUsed();

            // Create API token
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Sign in successful!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token
            ]);

        } catch (\Exception $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify magic link. Please try again.'
            ], 500);
        }
    }
}
