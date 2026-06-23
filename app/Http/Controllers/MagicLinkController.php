<?php

namespace App\Http\Controllers;

use App\Models\MagicLink;
use App\Models\User;
use App\Mail\MagicLinkEmail;
use App\Helpers\MailjetHelper;
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
        \Log::info('📝 [MAGIC_LINK] Registration request received');
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'quiz_data' => 'nullable|array',
            'quiz_token' => 'nullable|string'
        ]);

        try {
            \Log::info('📝 [MAGIC_LINK] Registration request started');

            // Check if user already exists
            if (User::where('email', $validated['email'])->exists()) {
                \Log::warning('⚠️ [MAGIC_LINK] User already exists');
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

            \Log::info('✨ [MAGIC_LINK] Magic link created', [
                'expires_at' => $magicLink->expires_at
            ]);

            // Build verification URL (frontend URL)
            $frontendUrl = config('app.frontend_url');
            $magicUrl = "{$frontendUrl}/auth/verify-magic-link?token={$token}";

            \Log::debug('🔗 [MAGIC_LINK] Magic link URL generated');

            // Send email via Mailjet API
            try {
                \Log::info('📧 [MAGIC_LINK] Attempting to send email via Mailjet', [
                    'template' => 'MagicLinkEmail'
                ]);

                $mailable = new MagicLinkEmail($magicLink, $magicUrl);
                $htmlContent = $mailable->render();
                
                \Log::debug('✍️ [MAGIC_LINK] Email content rendered', [
                    'content_length' => strlen($htmlContent)
                ]);

                MailjetHelper::sendEmail(
                    $validated['email'],
                    'Verify Your Email - Amen Our Roots Africa',
                    $htmlContent,
                    'Amen Our Roots Africa'
                );

                \Log::info('✅ [MAGIC_LINK] Email sent successfully');

            } catch (\Exception $emailError) {
                \Log::error('❌ [MAGIC_LINK] Email sending failed', [
                    'error' => $emailError->getMessage(),
                    'file' => $emailError->getFile(),
                    'line' => $emailError->getLine()
                ]);
                throw $emailError;
            }

            return response()->json([
                'success' => true,
                'message' => 'Magic link sent! Check your email (it expires in 15 minutes).'
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ [MAGIC_LINK] Registration request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
        \Log::info('📝 [MAGIC_LINK] Sign-in request received');

        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            \Log::info('📝 [MAGIC_LINK] Sign-in request started');

            // Check if user exists
            $user = User::where('email', $validated['email'])->first();
            
            if (!$user) {
                \Log::warning('⚠️ [MAGIC_LINK] User not found');
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

            \Log::info('👤 [MAGIC_LINK] User found', [
                'user_id' => $user->id
            ]);

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

            \Log::info('✨ [MAGIC_LINK] Magic link created for sign-in', [
                'user_id' => $user->id,
                'expires_at' => $magicLink->expires_at
            ]);

            // Build verification URL (frontend URL)
            $frontendUrl = config('app.frontend_url');
            $magicUrl = "{$frontendUrl}/auth/verify-magic-link?token={$token}";

            \Log::debug('🔗 [MAGIC_LINK] Sign-in link URL generated');

            // Send email via Mailjet API
            try {
                \Log::info('📧 [MAGIC_LINK] Attempting to send sign-in email via Mailjet', [
                    'template' => 'MagicLinkEmail'
                ]);

                $mailable = new MagicLinkEmail($magicLink, $magicUrl);
                $htmlContent = $mailable->render();
                
                \Log::debug('✍️ [MAGIC_LINK] Email content rendered for sign-in', [
                    'content_length' => strlen($htmlContent)
                ]);

                MailjetHelper::sendEmail(
                    $validated['email'],
                    'Sign in to Amen Our Roots Africa',
                    $htmlContent,
                    'Amen Our Roots Africa'
                );

                \Log::info('✅ [MAGIC_LINK] Sign-in email sent successfully');

            } catch (\Exception $emailError) {
                \Log::error('❌ [MAGIC_LINK] Sign-in email sending failed', [
                    'error' => $emailError->getMessage(),
                    'file' => $emailError->getFile(),
                    'line' => $emailError->getLine()
                ]);
                throw $emailError;
            }

            return response()->json([
                'success' => true,
                'message' => 'Magic link sent! Check your email (it expires in 15 minutes).'
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ [MAGIC_LINK] Sign-in request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
            \Log::info('🔐 [MAGIC_LINK] Verification request started');

            $magicLink = MagicLink::where('token', $validated['token'])->first();

            // Check if token exists
            if (!$magicLink) {
                \Log::warning('⚠️ [MAGIC_LINK] Invalid token provided');
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid magic link.'
                ], 404);
            }

            \Log::info('✓ [MAGIC_LINK] Token found', [
                'used' => $magicLink->used
            ]);

            // Check if already used
            if ($magicLink->used) {
                \Log::warning('⚠️ [MAGIC_LINK] Token already used');
                return response()->json([
                    'success' => false,
                    'message' => 'This magic link has already been used.'
                ], 409);
            }

            // Check if expired
            if (!$magicLink->isValid()) {
                \Log::warning('⚠️ [MAGIC_LINK] Token expired', [
                    'expires_at' => $magicLink->expires_at
                ]);
                $magicLink->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'This magic link has expired. Please register again.'
                ], 410);
            }

            \Log::info('✓ [MAGIC_LINK] Token is valid and not used');

            // Check if user already exists (sign-in) or create new user (registration)
            $user = User::where('email', $magicLink->email)->first();
            $isNewUser = false;

            if (!$user) {
                // Registration: Create new user
                \Log::info('👤 [MAGIC_LINK] Creating new user account');
                $isNewUser = true;

                $user = User::create([
                    'name' => $magicLink->name,
                    'email' => $magicLink->email,
                    'whatsapp' => $magicLink->whatsapp,
                    'role' => 'customer', // Default role
                    'password' => Hash::make(\Str::random(32)), // Random password since using magic link
                    'email_verified_at' => now(),
                ]);

                \Log::info('✅ [MAGIC_LINK] New user created successfully', [
                    'user_id' => $user->id
                ]);

                // Store quiz data if provided
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
                    $progress = \App\Models\UserProgress::firstOrNew(['user_id' => $user->id]);
                    $progress->afro_score = $quizData['totalScore'] ?? 0;
                    $progress->user_persona = $quizData['persona'] ?? 'Heritage Seeker';
                    $progress->save();

                    \Log::debug('💾 [MAGIC_LINK] Quiz data, progress, and onboarding status stored for new user', ['user_id' => $user->id]);
                }
            } else {
                // Sign-in: User already exists
                \Log::info('👤 [MAGIC_LINK] Existing user signing in', [
                    'user_id' => $user->id
                ]);
            }

            // Mark magic link as used
            $magicLink->markAsUsed();
            \Log::debug('✓ [MAGIC_LINK] Magic link marked as used');

            // Create API token
            $token = $user->createToken('auth-token')->plainTextToken;
            \Log::info('🔑 [MAGIC_LINK] API token created', ['user_id' => $user->id]);

            \Log::info('✅ [MAGIC_LINK] Verification completed successfully', [
                'user_id' => $user->id
            ]);

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
            \Log::error('❌ [MAGIC_LINK] Verification failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify magic link. Please try again.'
            ], 500);
        }
    }
}
