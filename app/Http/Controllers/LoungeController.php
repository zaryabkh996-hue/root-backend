<?php

namespace App\Http\Controllers;

use App\Models\LoungePost;
use App\Models\LoungePostLike;
use App\Models\LoungePostReply;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LoungeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $page = (int) $request->query('page', 1);
            $limit = (int) $request->query('limit', 10);
            $category = $request->query('category', '');

            $query = LoungePost::with(['user', 'replies.user'])->latest();

            if ($category) {
                $query->where('category', $category);
            }

            $total = $query->count();
            $posts = $query->paginate($limit, ['*'], 'page', $page);

            // Add isLiked field for the current user using a single query to avoid N+1 queries
            $userId = $request->user()->id;
            $postIds = $posts->pluck('id');
            $likedPostIds = LoungePostLike::where('user_id', $userId)
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->flip()
                ->toArray();

            $items = $posts->getCollection()->map(function ($post) use ($likedPostIds) {
                $post->isLiked = isset($likedPostIds[$post->id]);
                return $post;
            });

            return response()->json([
                'posts' => $items,
                'total' => $total,
                'currentPage' => $page,
                'totalPages' => ceil($total / $limit),
            ]);
        } catch (\Exception $e) {
            Log::error('LoungeController::index - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch posts'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string',
                'category' => 'required|in:question,tip,discussion',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $post = LoungePost::create([
                'user_id' => $request->user()->id,
                'content' => $request->content,
                'category' => $request->category,
            ]);

            $post->refresh();
            $post->load('user');

            return response()->json([
                'success' => true,
                'post' => $post,
                'message' => 'Post created successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('LoungeController::store - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create post'], 500);
        }
    }

    public function toggleLike(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $post = LoungePost::findOrFail($id);

            $like = LoungePostLike::where('user_id', $userId)->where('post_id', $id)->first();

            if ($like) {
                $like->delete();
                $post->decrement('likes_count');
                $isLiked = false;
            } else {
                LoungePostLike::create([
                    'user_id' => $userId,
                    'post_id' => $id,
                ]);
                $post->increment('likes_count');
                $isLiked = true;
            }

            return response()->json([
                'success' => true,
                'likes_count' => $post->likes_count,
                'isLiked' => $isLiked,
            ]);
        } catch (\Exception $e) {
            Log::error('LoungeController::toggleLike - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to toggle like'], 500);
        }
    }

    public function reply(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $post = LoungePost::findOrFail($id);

            $reply = LoungePostReply::create([
                'user_id' => $request->user()->id,
                'post_id' => $id,
                'content' => $request->content,
            ]);

            $post->increment('replies_count');
            $reply->load('user');

            return response()->json([
                'success' => true,
                'reply' => $reply,
                'replies_count' => $post->replies_count,
                'message' => 'Reply posted successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('LoungeController::reply - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to post reply'], 500);
        }
    }

    public function stats(Request $request)
    {
        try {
            $totalMembers = User::where('role', 'custodian')->count();
            $activeToday = User::where('role', 'custodian')
                ->where('updated_at', '>=', now()->startOfDay())
                ->count();
            
            $userId = $request->user()->id;
            $userStats = [
                'posts' => LoungePost::where('user_id', $userId)->count(),
                'replies' => LoungePostReply::where('user_id', $userId)->count(),
                'likes_received' => LoungePost::where('user_id', $userId)->sum('likes_count'),
            ];

            $hotTopics = LoungePost::orderBy('replies_count', 'desc')
                ->orderBy('likes_count', 'desc')
                ->limit(4)
                ->get(['id', 'content', 'category', 'replies_count']);

            return response()->json([
                'totalMembers' => $totalMembers,
                'activeToday' => $activeToday,
                'userStats' => $userStats,
                'hotTopics' => $hotTopics,
            ]);
        } catch (\Exception $e) {
            Log::error('LoungeController::stats - Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch stats'], 500);
        }
    }
}
