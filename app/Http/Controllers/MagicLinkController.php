<?php

namespace App\Http\Controllers;

use App\Models\MagicLink;
use App\Models\User;
use App\Models\UserProgress;    
use App\Mail\MagicLinkEmail;
use App\Helpers\ResendHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            'name' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'quiz_data' => 'nullable|array',
            'quiz_token' => 'nullable|string'
        ]);

        try {
            // Check if user already exists
            if (User::where('email', $validated['email'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An account with this email already exists. Please sign in instead.'
                ], 409);
            }

            // Resolve quiz token if provided
            $quizData = $validated['quiz_data'] ?? null;
            if (!empty($validated['quiz_token'])) {
                $resolved = \Illuminate\Support\Facades\Cache::get("quiz:{$validated['quiz_token']}");
                if ($resolved) {
                    $quizData = $resolved;
                }
            }

            // Revoke any existing unused magic links for this email
            MagicLink::where('email', $validated['email'])->delete();

            // Create new magic link
            $token = MagicLink::generateToken();
            $magicLink = MagicLink::create([
                'email' => $validated['email'],
                'token' => $token,
                'name' => $validated['name'] ?? 'User',
                'whatsapp' => $validated['whatsapp'] ?? null,
                'quiz_data' => $quizData,
                'expires_at' => now()->addMinutes(15)
            ]);


            // Build verification URL (frontend URL)
            $frontendUrl = config('app.frontend_url');
            $magicUrl = "{$frontendUrl}/auth/verify-magic-link?token={$token}";


            // Send email via Resend API
            try {
                $mailable = new MagicLinkEmail($magicLink, $magicUrl);
                $htmlContent = $mailable->render();

                ResendHelper::sendEmail(
                    $validated['email'],
                    'Verify Your Email - Amen Our Roots Africa',
                    $htmlContent,
                    'Amen Our Roots Africa'
                );


            } catch (\Exception $emailError) {
                throw $emailError;
            }

            return response()->json([
                'success' => true,
                'message' => 'Magic link sent! Check your email (it expires in 15 minutes).'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send magic link (registration): ' . $e->getMessage(), [
                'email' => $validated['email'] ?? null,
                'exception' => $e
            ]);
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
            $user = User::where('email', $validated['email'])->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this email.',
                    'code' => 'USER_NOT_FOUND',
                    'guidance' => [
                        'customer' => 'Please complete our quiz and register to create an account.',
                        'custodian' => 'If you are a custodian/admin, please contact the main administrator for account access.'
                    ]
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

            $frontendUrl = config('app.frontend_url');
            $magicUrl = "{$frontendUrl}/auth/verify-magic-link?token={$token}";

            // Send email via Resend API
            try {
                $mailable = new MagicLinkEmail($magicLink, $magicUrl);
                $htmlContent = $mailable->render();
                
                ResendHelper::sendEmail(
                    $validated['email'],
                    'Sign in to Amen Our Roots Africa',
                    $htmlContent,
                    'Amen Our Roots Africa'
                );


            } catch (\Exception $emailError) {
                throw $emailError;
            }

            return response()->json([
                'success' => true,
                'message' => 'Magic link sent! Check your email (it expires in 15 minutes).'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send magic link (sign-in): ' . $e->getMessage(), [
                'email' => $validated['email'] ?? null,
                'exception' => $e
            ]);
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
            if ($magicLink->used) {
                return response()->json([
                    'success' => false,
                    'message' => 'This magic link has already been used.'
                ], 409);
            }

            if (!$magicLink->isValid()) {
                $magicLink->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'This magic link has expired. Please register again.'
                ], 410);
            }


            $user = User::where('email', $magicLink->email)->first();
            $isNewUser = false;

            if (!$user) {
                $isNewUser = true;

                $user = User::create([
                    'name' => $magicLink->name,
                    'email' => $magicLink->email,
                    'whatsapp' => $magicLink->whatsapp,
                    'role' => 'customer', // Default role
                    'password' => Hash::make(\Str::random(32)), // Random password since using magic link
                    'email_verified_at' => now(),
                ]);


                if ($magicLink->quiz_data) {
                    $quizData = $magicLink->quiz_data;
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
                    $progress =UserProgress::firstOrNew(['user_id' => $user->id]);
                    $progress->afro_score = $quizData['totalScore'] ?? 0;
                    $progress->user_persona = $quizData['persona'] ?? 'Heritage Seeker';
                    $progress->save();

                }
            }

            $magicLink->markAsUsed();
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Sign in successful!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'onboarded' => (bool)$user->onboarded,
                    'learning_preference' => $user->learning_preference,
                    'travel_date' => $user->travel_date,
                    'quiz_data' => $user->quiz_data,
                    'is_new_user' => $isNewUser,
                ],
                'token' => $token
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to verify magic link: ' . $e->getMessage(), [
                'token' => $validated['token'] ?? null,
                'exception' => $e
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify magic link. Please try again.'
            ], 500);
        }
    }
}
