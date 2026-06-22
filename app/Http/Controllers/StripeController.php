<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeController extends Controller
{
    private $stripe;

    public function __construct()
    {
        $secret = config('services.stripe.secret');
        if (!empty($secret)) {
            $this->stripe = new StripeClient($secret);
        }
    }

    /**
     * Create a Stripe Checkout Session for a subscription (MOCK FLOW - ACTIVE)
     */
    public function createCheckoutSession(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $request->validate([
                'tier' => 'required|string|in:community,preparation',
            ]);

            $tier = $request->input('tier');

            // Frontend URLs
            $frontendUrl = env('APP_FRONTEND_URL', 'http://localhost:3000');
            $successUrl = rtrim($frontendUrl, '/') . '/subscription/success?session_id=mock_session_' . uniqid();

            // MOCK MODE: Bypass Stripe checkout session creation
            // Directly upgrade user tier
            $user->update([
                'subscription_tier' => $tier,
                'subscription_status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'url' => $successUrl,
            ], 200);

        } catch (\Exception $e) {
            Log::error('[StripeController] createCheckoutSession (Mock) error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create checkout session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a Stripe Checkout Session for a subscription (REAL STRIPE FLOW - INACTIVE)
     * To activate this: uncomment this function, and comment out the mock version above.
     */
    /*
    public function createCheckoutSession(Request $request)
    {
        if (!$this->stripe) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe Secret Key is not configured in the backend .env file. Please add STRIPE_SECRET.'
            ], 500);
        }

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $request->validate([
                'tier' => 'required|string|in:community,preparation',
            ]);

            $tier = $request->input('tier');
            
            // Map tier to Price ID from config
            $priceId = $tier === 'community' 
                ? config('services.stripe.price_community') 
                : config('services.stripe.price_preparation');

            if (!$priceId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe Price ID is not configured for ' . $tier . ' tier.',
                ], 500);
            }

            // Create Stripe Customer if user doesn't have one
            $stripeCustomerId = $user->stripe_id;
            if (!$stripeCustomerId) {
                $customer = $this->stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => [
                        'user_id' => $user->id,
                    ],
                ]);
                $stripeCustomerId = $customer->id;
                $user->update(['stripe_id' => $stripeCustomerId]);
            }

            // Frontend URLs
            // We read the frontend URL from config/env
            $frontendUrl = env('APP_FRONTEND_URL', 'http://localhost:3000');
            $successUrl = rtrim($frontendUrl, '/') . '/subscription/success?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = rtrim($frontendUrl, '/') . '/#pricing';

            $session = $this->stripe->checkout->sessions->create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'user_id' => $user->id,
                    'tier' => $tier,
                ],
            ]);

            return response()->json([
                'success' => true,
                'url' => $session->url,
            ], 200);

        } catch (\Exception $e) {
            Log::error('[StripeController] createCheckoutSession error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create checkout session',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    */

    /**
     * Create a Stripe Billing Portal Session for subscription management
     */
    public function createPortalSession(Request $request)
    {
        if (!$this->stripe) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe Secret Key is not configured in the backend .env file. Please add STRIPE_SECRET.'
            ], 500);
        }

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            if (!$user->stripe_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have an active billing history. Start a subscription first.',
                ], 400);
            }

            $frontendUrl = env('APP_FRONTEND_URL', 'http://localhost:3000');
            $returnUrl = rtrim($frontendUrl, '/') . '/profile';

            $session = $this->stripe->billingPortal->sessions->create([
                'customer' => $user->stripe_id,
                'return_url' => $returnUrl,
            ]);

            return response()->json([
                'success' => true,
                'url' => $session->url,
            ], 200);

        } catch (\Exception $e) {
            Log::error('[StripeController] createPortalSession error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create billing portal session',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle Stripe Webhook Events
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        Log::info('[StripeWebhook] Webhook received');

        try {
            if ($webhookSecret) {
                $event = \Stripe\Webhook::constructEvent(
                    $payload, $sigHeader, $webhookSecret
                );
            } else {
                Log::warning('[StripeWebhook] STRIPE_WEBHOOK_SECRET is not configured. Signature verification skipped.');
                $data = json_decode($payload, true);
                $event = \Stripe\Event::constructFrom($data);
            }
        } catch (UnexpectedValueException $e) {
            Log::error('[StripeWebhook] Invalid payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('[StripeWebhook] Invalid signature: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('[StripeWebhook] Event type: ' . $event->type);

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutSessionCompleted($session);
                break;
            case 'customer.subscription.updated':
                $subscription = $event->data->object;
                $this->handleSubscriptionUpdated($subscription);
                break;
            case 'customer.subscription.deleted':
                $subscription = $event->data->object;
                $this->handleSubscriptionDeleted($subscription);
                break;
            default:
                Log::info('[StripeWebhook] Unhandled event type: ' . $event->type);
        }

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Handle checkout.session.completed
     */
    private function handleCheckoutSessionCompleted($session)
    {
        if (!$this->stripe) {
            Log::error("[StripeWebhook] Cannot handle checkout.session.completed. Stripe SDK is not configured.");
            return;
        }

        $userId = $session->metadata->user_id ?? null;
        $tier = $session->metadata->tier ?? null;
        $customerId = $session->customer;
        $subscriptionId = $session->subscription;

        Log::info("[StripeWebhook] Handling checkout.session.completed", [
            'user_id' => $userId,
            'tier' => $tier,
            'subscription_id' => $subscriptionId
        ]);

        if (!$userId) {
            $user = User::where('stripe_id', $customerId)->first();
        } else {
            $user = User::find($userId);
        }

        if ($user) {
            try {
                $subscription = $this->stripe->subscriptions->retrieve($subscriptionId);
                
                if (!$tier) {
                    $priceId = $subscription->items->data[0]->price->id ?? null;
                    if ($priceId === config('services.stripe.price_community')) {
                        $tier = 'community';
                    } elseif ($priceId === config('services.stripe.price_preparation')) {
                        $tier = 'preparation';
                    } else {
                        $tier = 'free';
                    }
                }

                $user->update([
                    'stripe_id' => $customerId,
                    'stripe_subscription_id' => $subscriptionId,
                    'stripe_price_id' => $subscription->items->data[0]->price->id ?? null,
                    'subscription_status' => $subscription->status,
                    'subscription_tier' => $tier,
                    'subscription_ends_at' => $subscription->current_period_end ? date('Y-m-d H:i:s', $subscription->current_period_end) : null,
                ]);
                
                Log::info("[StripeWebhook] User {$user->id} subscription tier updated to {$tier}");
            } catch (\Exception $e) {
                Log::error("[StripeWebhook] Error fetching subscription details: " . $e->getMessage());
            }
        } else {
            Log::error("[StripeWebhook] User not found for customer {$customerId}");
        }
    }

    /**
     * Handle customer.subscription.updated
     */
    private function handleSubscriptionUpdated($subscription)
    {
        $customerId = $subscription->customer;
        $subscriptionId = $subscription->id;
        $status = $subscription->status;
        $priceId = $subscription->items->data[0]->price->id ?? null;

        Log::info("[StripeWebhook] Handling customer.subscription.updated", [
            'customer_id' => $customerId,
            'subscription_id' => $subscriptionId,
            'status' => $status
        ]);

        $user = User::where('stripe_id', $customerId)->first();
        if ($user) {
            if ($status === 'active' || $status === 'trialing') {
                if ($priceId === config('services.stripe.price_community')) {
                    $tier = 'community';
                } elseif ($priceId === config('services.stripe.price_preparation')) {
                    $tier = 'preparation';
                } else {
                    $tier = 'free';
                }
            } else {
                $tier = ($status === 'past_due') ? $user->subscription_tier : 'free';
            }

            $user->update([
                'stripe_subscription_id' => $subscriptionId,
                'stripe_price_id' => $priceId,
                'subscription_status' => $status,
                'subscription_tier' => $tier,
                'subscription_ends_at' => $subscription->current_period_end ? date('Y-m-d H:i:s', $subscription->current_period_end) : null,
            ]);

            Log::info("[StripeWebhook] User {$user->id} subscription updated. Status: {$status}, Tier: {$tier}");
        } else {
            Log::error("[StripeWebhook] User not found for customer {$customerId}");
        }
    }

    /**
     * Handle customer.subscription.deleted
     */
    private function handleSubscriptionDeleted($subscription)
    {
        $customerId = $subscription->customer;
        $subscriptionId = $subscription->id;

        Log::info("[StripeWebhook] Handling customer.subscription.deleted", [
            'customer_id' => $customerId,
            'subscription_id' => $subscriptionId
        ]);

        $user = User::where('stripe_id', $customerId)->first();
        if ($user) {
            $user->update([
                'stripe_subscription_id' => null,
                'stripe_price_id' => null,
                'subscription_status' => 'canceled',
                'subscription_tier' => 'free',
                'subscription_ends_at' => null,
            ]);

            Log::info("[StripeWebhook] User {$user->id} subscription deleted. Tier set to free.");
        } else {
            Log::error("[StripeWebhook] User not found for customer {$customerId}");
        }
    }
}
