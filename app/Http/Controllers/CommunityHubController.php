<?php

namespace App\Http\Controllers;

use App\Models\CommunityHub;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CommunityHubController extends Controller
{
    // Get all active hubs with member counts
    public function index(): JsonResponse
    {
        $hubs = CommunityHub::where('is_active', true)
            ->with(['members', 'threads'])
            ->get()
            ->map(function ($hub) {
                return [
                    'id' => $hub->id,
                    'name' => $hub->name,
                    'slug' => $hub->slug,
                    'adinkra' => $hub->adinkra,
                    'emoji' => $hub->emoji,
                    'description' => $hub->description,
                    'access_level' => $hub->access_level,
                    'access_label' => $hub->access_label,
                    'border_color' => $hub->border_color,
                    'members_count' => $hub->getMembersCount(),
                    'active_threads_count' => $hub->getActiveThreadsCount(),
                    'user_is_member' => auth()->check() ? $hub->userIsMember(auth()->id()) : false,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $hubs,
        ]);
    }

    // Get single hub with threads
    public function show($id): JsonResponse
    {
        $hub = CommunityHub::with(['threads.author', 'members'])
            ->find($id);

        if (!$hub) {
            return response()->json(['error' => 'Hub not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $hub->id,
                'name' => $hub->name,
                'slug' => $hub->slug,
                'adinkra' => $hub->adinkra,
                'emoji' => $hub->emoji,
                'description' => $hub->description,
                'access_level' => $hub->access_level,
                'access_label' => $hub->access_label,
                'border_color' => $hub->border_color,
                'members_count' => $hub->getMembersCount(),
                'active_threads_count' => $hub->getActiveThreadsCount(),
                'user_is_member' => auth()->check() ? $hub->userIsMember(auth()->id()) : false,
                'threads' => $hub->threads->map(function ($thread) {
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
                }),
            ],
        ]);
    }

    // Create new hub (Admin only)
    public function store(Request $request): JsonResponse
    {
        // Check if user is admin
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:community_hubs',
            'adinkra' => 'required|string',
            'emoji' => 'required|string',
            'description' => 'required|string',
            'access_level' => 'required|in:free,community,preparation,locked',
            'access_label' => 'required|string',
            'border_color' => 'nullable|string',
        ]);

        $hub = CommunityHub::create([
            ...$validated,
            'slug' => Str::slug($validated['name']),
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hub created successfully',
            'data' => $hub,
        ], 201);
    }

    // Update hub (Admin only)
    public function update(Request $request, $id): JsonResponse
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $hub = CommunityHub::find($id);
        if (!$hub) {
            return response()->json(['error' => 'Hub not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'string|unique:community_hubs,name,' . $id,
            'adinkra' => 'string',
            'emoji' => 'string',
            'description' => 'string',
            'access_level' => 'in:free,community,preparation,locked',
            'access_label' => 'string',
            'border_color' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $hub->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Hub updated successfully',
            'data' => $hub,
        ]);
    }

    // Join hub
    public function join(Request $request, $id): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $hub = CommunityHub::find($id);
        if (!$hub) {
            return response()->json(['error' => 'Hub not found'], 404);
        }

        // Check if already a member
        if ($hub->userIsMember(auth()->id())) {
            return response()->json([
                'success' => true,
                'message' => 'Already a member of this hub',
            ]);
        }

        $hub->members()->attach(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Joined hub successfully',
        ]);
    }

    // Leave hub
    public function leave(Request $request, $id): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $hub = CommunityHub::find($id);
        if (!$hub) {
            return response()->json(['error' => 'Hub not found'], 404);
        }

        $hub->members()->detach(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Left hub successfully',
        ]);
    }
}
