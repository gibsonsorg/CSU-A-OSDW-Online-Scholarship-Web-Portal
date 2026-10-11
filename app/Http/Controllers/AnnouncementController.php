<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $user->forceFill(['announcements_seen_at' => now()])->saveQuietly();

        $announcements = Announcement::query()
            ->with(['images', 'adminUser:id,name'])
            ->withCount('comments')
            ->orderByRaw('pinned_at IS NULL')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        $commentRows = collect();
        if ($announcements->isNotEmpty()) {
            $rankedComments = DB::table('announcement_comments')
                ->join('users', 'users.id', '=', 'announcement_comments.user_id')
                ->whereIn('announcement_comments.announcement_id', $announcements->pluck('id'))
                ->select([
                    'announcement_comments.id',
                    'announcement_comments.announcement_id',
                    'announcement_comments.user_id',
                    'announcement_comments.body',
                    'announcement_comments.created_at',
                    'users.name as commenter_name',
                ])
                ->selectRaw('ROW_NUMBER() OVER (PARTITION BY announcement_comments.announcement_id ORDER BY announcement_comments.created_at DESC, announcement_comments.id DESC) AS comment_rank');

            $commentRows = DB::query()
                ->fromSub($rankedComments, 'ranked_comments')
                ->where('comment_rank', '<=', 3)
                ->orderBy('announcement_id')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->groupBy('announcement_id');
        }

        return view('student.announcements.index', compact('announcements', 'commentRows'));
    }

    public function comments(Request $request, Announcement $announcement): JsonResponse
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = 20;

        $comments = AnnouncementComment::query()
            ->where('announcement_id', $announcement->id)
            ->whereNotIn('id', function ($query) use ($announcement): void {
                $query->select('id')
                    ->from('announcement_comments')
                    ->where('announcement_id', $announcement->id)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(3);
            })
            ->with('user:id,name')
            ->orderBy('created_at')
            ->orderBy('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage + 1)
            ->get();

        $hasMore = $comments->count() > $perPage;
        $comments = $comments->take($perPage)->map(fn (AnnouncementComment $comment): array => $this->commentPayload($comment));

        return response()->json([
            'comments' => $comments,
            'page' => $page,
            'has_more' => $hasMore,
        ]);
    }

    public function storeComment(Request $request, Announcement $announcement): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ], [
            'body.required' => 'Write a comment before posting.',
            'body.max' => 'Comments cannot exceed 500 characters.',
        ]);

        $comment = $announcement->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);
        $comment->load('user:id,name');

        return response()->json(['comment' => $this->commentPayload($comment)], 201);
    }

    public function destroyOwnComment(Request $request, AnnouncementComment $comment): JsonResponse
    {
        abort_unless((int) $comment->user_id === (int) $request->user()->id, 403);
        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }

    private function commentPayload(AnnouncementComment $comment): array
    {
        return [
            'id' => $comment->id,
            'user_id' => $comment->user_id,
            'name' => $comment->user->name,
            'body' => $comment->body,
            'created_at' => $comment->created_at->diffForHumans(),
        ];
    }
}