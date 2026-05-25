<?php

namespace App\Http\Controllers;

use App\Models\CommunityThread;
use App\Models\CommunityReply;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommunityReplyController extends Controller
{
    // Get all replies for a thread
    public function indexByThread($threadId): JsonResponse
    {
        $thread = CommunityThread::find($threadId);
        if (!$thread) {
            return response()->json(['error' => 'Thread not found'], 404);
        }

        $replies = CommunityReply::where('thread_id', $threadId)
            ->with('author')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($reply) {
                return [
                    'id' => $reply->id,
                    'content' => $reply->content,
                    'author' => $reply->author->name,
                    'author_initials' => substr($reply->author->name, 0, 1),
                    'is_custodian' => $reply->author->role === 'custodian',
                    'created_at' => $reply->created_at->toIso8601String(),
                    'time_ago' => $reply->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $replies,
        ]);
    }

    // Create new reply
    public function store(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'thread_id' => 'required|exists:community_threads,id',
            'content' => 'required|string',
        ]);

        $thread = CommunityThread::find($validated['thread_id']);

        // Check if user is member of hub (unless admin)
        if (!$thread->hub->userIsMember(auth()->id()) && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'You must be a member of this hub to reply'], 403);
        }

        $reply = CommunityReply::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reply posted successfully',
            'data' => [
                'id' => $reply->id,
                'content' => $reply->content,
                'author' => $reply->author->name,
                'author_initials' => substr($reply->author->name, 0, 1),
                'is_custodian' => $reply->author->role === 'custodian',
                'created_at' => $reply->created_at->toIso8601String(),
                'time_ago' => $reply->created_at->diffForHumans(),
            ],
        ], 201);
    }

    // Update reply (own replies only)
    public function update(Request $request, $id): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $reply = CommunityReply::find($id);
        if (!$reply) {
            return response()->json(['error' => 'Reply not found'], 404);
        }

        if ($reply->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'string',
        ]);

        $reply->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Reply updated successfully',
            'data' => $reply,
        ]);
    }

    // Delete reply (own replies only or admin)
    public function destroy($id): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $reply = CommunityReply::find($id);
        if (!$reply) {
            return response()->json(['error' => 'Reply not found'], 404);
        }

        if ($reply->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reply->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reply deleted successfully',
        ]);
    }
}
