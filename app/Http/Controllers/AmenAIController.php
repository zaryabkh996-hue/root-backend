<?php

namespace App\Http\Controllers;

use App\Services\AmenAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * AmenAIController — Handles Amen AI chat interactions.
 *
 * Routes:
 *   POST  /api/amen-ai/chat     — Send message, get Amen's response
 *   GET   /api/amen-ai/history  — Get user's conversation sessions
 *   GET   /api/amen-ai/history/{conversationId} — Get messages for a session
 */
class AmenAIController extends Controller
{
    public function __construct(
        private AmenAIService $amenAIService,
    ) {}

    /**
     * Send a message to Amen AI and receive a response.
     *
     * The frontend generates a conversation_id (UUID) per session
     * and sends it with each message for continuity.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'         => 'required|string|max:5000',
            'conversation_id' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        $conversationId = $validated['conversation_id'] ?? Str::uuid()->toString();

        try {
            $result = $this->amenAIService->chat(
                message: $validated['message'],
                conversationId: $conversationId,
                client: $user,
            );

            return response()->json([
                'success' => true,
                'data'    => $result,
            ]);

        } catch (\Exception $e) {
            // Graceful degradation — always respond, even if AI is down
            return response()->json([
                'success' => false,
                'message' => 'I am having trouble responding right now. Please try again in a moment.',
                'data'    => [
                    'response'        => "I'm here, but I'm having a little difficulty right now. Please try again in a moment — I won't go far.",
                    'conversation_id' => $conversationId,
                    'fragment_used'   => false,
                    'custodian_name'  => null,
                    'model_used'      => 'fallback',
                ],
            ], 200); // 200 so frontend still renders the fallback message
        }
    }

    /**
     * Get the user's conversation sessions.
     */
    public function history(Request $request): JsonResponse
    {
        $conversations = $this->amenAIService->getUserConversations(
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'data'    => $conversations,
        ]);
    }

    /**
     * Get messages for a specific conversation session.
     */
    public function sessionMessages(Request $request, string $conversationId): JsonResponse
    {
        $messages = $this->amenAIService->getConversationMessages(
            conversationId: $conversationId,
            userId: $request->user()->id,
        );

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }
}
