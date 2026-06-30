<?php

namespace App\Http\Controllers;

use App\Models\CommunityReport;
use App\Models\CommunityThread;
use App\Models\CommunityReply;
use App\Models\User;
use App\Helpers\ResendHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CommunityReportController extends Controller
{
    /**
     * Store a new report (Authenticated users)
     */
    public function store(Request $request): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'item_type' => 'required|string|in:thread,reply',
            'item_id' => 'required|integer',
            'reason' => 'nullable|string',
        ]);

        $itemType = $validated['item_type'];
        $itemId = $validated['item_id'];
        $reporterId = auth()->id();
        $reportedUserId = null;

        // Verify that the reported item exists and get the author's user ID
        if ($itemType === 'thread') {
            $item = CommunityThread::find($itemId);
            if (!$item) {
                return response()->json(['error' => 'Reported thread not found'], 404);
            }
            $reportedUserId = $item->user_id;
        } else {
            $item = CommunityReply::find($itemId);
            if (!$item) {
                return response()->json(['error' => 'Reported reply not found'], 404);
            }
            $reportedUserId = $item->user_id;
        }

        // Prevent users from reporting their own posts
        if ($reportedUserId === $reporterId) {
            return response()->json(['error' => 'You cannot report your own post.'], 400);
        }

        // Check if already reported by this user
        $exists = CommunityReport::where('reporter_id', $reporterId)
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => true,
                'message' => 'You have already reported this post.',
                'already_reported' => true
            ]);
        }

        // Create the report
        $report = CommunityReport::create([
            'reporter_id' => $reporterId,
            'reported_user_id' => $reportedUserId,
            'item_type' => $itemType,
            'item_id' => $itemId,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post reported successfully',
            'data' => $report,
        ], 201);
    }

    /**
     * List all reports (Admin only)
     */
    public function index(Request $request): JsonResponse
    {
        // Add auth/role check if needed, routes middleware will protect it
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reports = CommunityReport::with(['reporter', 'reportedUser'])
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedReports = $reports->map(function ($report) {
            $itemTitle = 'Unknown';
            $itemContent = 'Content not available or deleted';

            if ($report->item_type === 'thread') {
                $thread = CommunityThread::find($report->item_id);
                if ($thread) {
                    $itemTitle = $thread->title;
                    $itemContent = $thread->content;
                }
            } elseif ($report->item_type === 'reply') {
                $reply = CommunityReply::with('thread')->find($report->item_id);
                if ($reply) {
                    $itemTitle = 'Reply in: ' . ($reply->thread->title ?? 'Deleted Thread');
                    $itemContent = $reply->content;
                }
            }

            return [
                'id' => $report->id,
                'reporter_name' => $report->reporter->name ?? 'Unknown Reporter',
                'reported_user_name' => $report->reportedUser->name ?? 'Unknown User',
                'reported_user_id' => $report->reported_user_id,
                'reported_user_email' => $report->reportedUser->email ?? null,
                'item_type' => $report->item_type,
                'item_id' => $report->item_id,
                'item_title' => $itemTitle,
                'item_content' => $itemContent,
                'reason' => $report->reason,
                'status' => $report->status,
                'warning_message' => $report->warning_message,
                'created_at' => $report->created_at->toIso8601String(),
                'time_ago' => $report->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedReports,
        ]);
    }

    /**
     * Issue a formal warning (Admin only)
     */
    public function warn(Request $request, $id): JsonResponse
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $report = CommunityReport::find($id);
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        if ($report->status === 'dismissed' || $report->status === 'banned') {
            return response()->json(['error' => 'Cannot issue warning on a ' . $report->status . ' report.'], 400);
        }

        $reportedUser = User::find($report->reported_user_id);
        if (!$reportedUser) {
            return response()->json(['error' => 'Reported user not found'], 404);
        }

        // Send Email
        try {
            $subject = "Formal Warning: Community Guidelines Violation";
            $htmlContent = "
                <div style='font-family: sans-serif; padding: 20px; line-height: 1.6; color: #333;'>
                    <h2 style='color: #b91c1c;'>Community Guidelines Notice</h2>
                    <p>Dear {$reportedUser->name},</p>
                    <p>This is a formal warning regarding your post or reply on <strong>Amen Our Roots Africa</strong>.</p>
                    <div style='background-color: #fef2f2; border-left: 4px solid #b91c1c; padding: 15px; margin: 15px 0;'>
                        <strong>Message from Moderation Team:</strong><br>
                        " . nl2br(e($validated['message'])) . "
                    </div>
                    <p>Please review our Code of Conduct and ensure all future contributions align with these guidelines to avoid further action, up to and including permanent suspension of your account.</p>
                    <p>Sincerely,<br>Amen Our Roots Africa Admin Panel</p>
                </div>
            ";

            ResendHelper::sendEmail($reportedUser->email, $subject, $htmlContent);
            Log::info("Formal warning email sent successfully to User ID: {$reportedUser->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send warning email to User ID: {$reportedUser->id}. Error: " . $e->getMessage());
            return response()->json(['error' => 'Failed to send email: ' . $e->getMessage()], 500);
        }

        // Update report status
        $report->status = 'warned';
        $report->warning_message = $validated['message'];
        $report->save();

        return response()->json([
            'success' => true,
            'message' => 'Formal warning sent and report updated.',
            'data' => $report,
        ]);
    }

    /**
     * Permanent ban (Admin only)
     */
    public function ban(Request $request, $id): JsonResponse
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $report = CommunityReport::find($id);
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        $reportedUser = User::find($report->reported_user_id);
        if (!$reportedUser) {
            return response()->json(['error' => 'Reported user not found'], 404);
        }

        // Ban the user by changing status to suspended
        $reportedUser->status = 'suspended';
        $reportedUser->save();

        // Update the report status to banned
        $report->status = 'banned';
        $report->save();

        return response()->json([
            'success' => true,
            'message' => 'User account has been permanently suspended (banned).',
            'data' => $report,
        ]);
    }

    /**
     * Dismiss report (Admin only)
     */
    public function dismiss(Request $request, $id): JsonResponse
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $report = CommunityReport::find($id);
        if (!$report) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        // Update report status
        $report->status = 'dismissed';
        $report->save();

        return response()->json([
            'success' => true,
            'message' => 'Report has been dismissed.',
            'data' => $report,
        ]);
    }
}
