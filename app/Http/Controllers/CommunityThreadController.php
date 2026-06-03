<?php

namespace App\Http\Controllers;

use App\Models\CommunityHub;
use App\Models\CommunityThread;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommunityThreadController extends Controller
{
    // Get all threads in a hub
    public function indexByHub($hubId): JsonResponse
    {
        $hub = CommunityHub::find($hubId);
        if (!$hub) {
            return response()->json(['error' => 'Hub not found'], 404);
        }

        $threads = CommunityThread::where('hub_id', $hubId)
            ->where('is_active', true)
            ->with(['author', 'replies'])
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($thread) {
                return [
                    'id' => $thread->id,
                    'title' => $thread->title,
                    'excerpt' => substr($thread->content, 0, 150) . '...',
                    'author' => $thread->author->name,
                    'author_initials' => substr($thread->author->name, 0, 1),
                    'time_ago' => $thread->created_at->diffForHumans(),
                    'replies_count' => $thread->getRepliesCount(),
                    'last_reply_time' => $thread->getLastReply() ? $thread->getLastReply()->created_at->diffForHumans() : 'Never',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $threads,
        ]);
    }

    // Get single thread with replies
    public function show($id): JsonResponse
    {
        $thread = CommunityThread::with(['author', 'hub', 'replies.author'])
            ->find($id);

        if (!$thread) {
            return response()->json(['error' => 'Thread not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $thread->id,
                'user_id' => $thread->user_id,
                'title' => $thread->title,
                'content' => $thread->content,
                'author' => $thread->author->name,
                'author_full' => $thread->author->name,
                'author_initials' => substr($thread->author->name, 0, 1),
                'location' => $thread->location,
                'user_stage' => $thread->user_stage,
                'user_tier' => $thread->user_tier,
                'created_at' => $thread->created_at->toIso8601String(),
                'time_ago' => $thread->created_at->diffForHumans(),
                'hub_id' => $thread->hub_id,
                'hub_name' => $thread->hub->name,
                'replies' => $thread->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'user_id' => $reply->user_id,
                        'content' => $reply->content,
                        'author' => $reply->author->name,
                        'author_initials' => substr($reply->author->name, 0, 1),
                        'is_custodian' => $reply->author->role === 'custodian',
                        'created_at' => $reply->created_at->toIso8601String(),
                        'time_ago' => $reply->created_at->diffForHumans(),
                    ];
                }),
            ],
        ]);
    }

    // Create new thread
    public function store(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'hub_id' => 'required|exists:community_hubs,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'location' => 'nullable|string',
            'user_stage' => 'nullable|string',
            'user_tier' => 'nullable|string',
        ]);

        // Check if user is member of hub
        $hub = CommunityHub::find($validated['hub_id']);
        if (!$hub->userIsMember(auth()->id()) && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'You must be a member of this hub to post'], 403);
        }

        $thread = CommunityThread::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thread created successfully',
            'data' => $thread,
        ], 201);
    }

    // Update thread (own posts only)
    public function update(Request $request, $id): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $thread = CommunityThread::find($id);
        if (!$thread) {
            return response()->json(['error' => 'Thread not found'], 404);
        }

        if ($thread->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'string|max:255',
            'content' => 'string',
            'is_active' => 'boolean',
        ]);

        $thread->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Thread updated successfully',
            'data' => $thread,
        ]);
    }

    // Delete thread (own posts only or admin)
    public function destroy($id): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $thread = CommunityThread::find($id);
        if (!$thread) {
            return response()->json(['error' => 'Thread not found'], 404);
        }

        if ($thread->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $thread->delete();

        return response()->json([
            'success' => true,
            'message' => 'Thread deleted successfully',
        ]);
    }
}
