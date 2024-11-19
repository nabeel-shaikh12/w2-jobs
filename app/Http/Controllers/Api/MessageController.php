<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $conversations = Conversation::query()
            ->with([
                'lastReply',
                'receiver:id,name,avatar,created_at,category_id'
            ])
            ->when($request->name, function ($query) {
                $query->whereHas('users', function ($q) {
                    $q->where('name', 'like', '%' . request()->name . '%');
                });
            })
            ->when($request->read, function ($query) {
                $query->whereHas('replies', function ($q) {
                    if (request()->read == 'read') $q->whereNotNull('read_at');
                    if (request()->read == 'unread') $q->whereNull('read_at');
                });
            })
            ->whereHas('users', function ($query) {
                $query->where('user_id', auth()->id());
            })->orderBy('created_at', 'desc')->paginate(4);
        return response()->json($conversations);
    }
    public function show($uuid)
    {
        $conversation = Conversation::query()
            ->where('uuid', $uuid)
            ->firstOrFail();
        $replies = $conversation->replies()
            ->with(['user:id,name,avatar,created_at,email'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($replies);
    }
}
