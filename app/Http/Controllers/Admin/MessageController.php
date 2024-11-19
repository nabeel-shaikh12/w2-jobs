<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:message-requests');
    }


    public function index(Request $request)
    {
        $conversations = Conversation::query();

        if ($request->search !== null && $request->search !== '') {
            $conversations = match ($request->type) {
                'name', 'email' => $conversations->whereHas('users', fn ($q) => $q->where($request->type, $request->search)),
                default => $conversations->where($request->type, 'LIKE', '%' . $request->search . '%'),
            };
        }
        $conversations = $conversations
            ->with(['users:id,name,email,role,avatar,created_at'])
            ->withCount('replies')
            ->paginate();
        $totalConversation = Conversation::count();
        $totalDeleted = Conversation::whereNotNull('deleted_by')->count();
        $totalBlocked = Conversation::whereNotNull('blocked_by')->count();

        $type = $request->type ?? 'email';

        return Inertia::render('Admin/Message/Index', [
            'conversations' => $conversations,
            'type' => $type,
            'request' => $request,
            'totalConversation' => $totalConversation,
            'totalDeleted' => $totalDeleted,
            'totalBlocked' => $totalBlocked
        ]);
    }

    public function show($uuid)
    {
        $conversation = Conversation::where('uuid', $uuid)->firstOrFail();
        $segments = request()->segments();
        $buttons = [
            [
                'name' => __('Back'),
                'url' => route('admin.message.index')
            ],
        ];

        $replies = $conversation->replies()->with('user:id,name,email,avatar,created_at')->paginate();
        $totalReplies = $conversation->replies()->count();
        return Inertia::render('Admin/Message/Show', [
            'conversation' => $conversation,
            'segments' => $segments,
            'buttons' => $buttons,
            'replies' => $replies,
            'totalReplies' => $totalReplies
        ]);
    }


    public function destroy($id)
    {
        $conversation = Conversation::findOrFail($id);
        $conversation->delete();

        return back();
    }
}
