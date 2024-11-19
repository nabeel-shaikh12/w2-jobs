<?php

namespace App\Http\Controllers\User;

use App\Events\Reply;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MessageController extends Controller
{
    use Uploader;
    public function index()
    {
        return Inertia::render('User/Message/Index');
    }

    public function show($uuid)
    {
        $conversation =  Conversation::query()
            ->with([
                'receiver:id,email,name,username,avatar,created_at'
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();
        if (!$conversation->deleted_by || $conversation->deleted_by && $conversation->deleted_by != auth()->id()) {
            $conversation->replies()
                ->whereNot('user_id', auth()->id())
                ->whereNull('read_at')->update(['read_at' => now()]);
            $replies = $conversation->replies()->with([
                'user:id,name,avatar,created_at,email',
            ])->orderByDesc('created_at')->paginate(10);
        }
        return Inertia::render('User/Message/Show', [
            'conversation' => $conversation,
            'replies' => $replies ?? []
        ]);
    }

    public function store(Request $request)
    {
        if ($request->filled('email')) {
            $request->validate([
                'body' => ['required', 'string'],
                'email' => ['required', 'email', 'exists:users,email'],
            ]);
            $receiver = User::query()->where('email', $request->email)
                ->whereRole('employer')
                ->firstOrFail();
            $conversation = $receiver->conversations()
                ->whereHas('users', function ($q) {
                    $q->where('user_id', auth()->id());
                });
            if ($conversation->exists()) {
                $conversation->first()->replies()->create([
                    'body' => $request->body,
                    'user_id' => auth()->id(),
                ]);
                return to_route('user.message.show', $conversation->first()->uuid);
            }
            return back();
        }
        $request->validate([
            'body' => ['nullable', Rule::requiredIf(!$request->hasFile('attachments')), 'string'],
        ]);
        if ($request->hasFile('attachments')) {
            $request->validate([
                'attachments.*' => 'required|file|mimes:pdf,doc,docx,csv,xlsx,xls,png,jpg,jpeg,gif,svg,webp|max:5000',
            ]);
            $attachments = $this->multipleSaveFile($request, 'attachments');
            $attachments = json_encode($attachments);
        }

        $sender = auth()->user();
        $conversation = Conversation::query()
            ->where('uuid', $request->input('uuid'))
            ->firstOrFail();

        $reply = $conversation->replies()->create([
            'body' => $request->body,
            'user_id' => $sender->id,
            'attachments' => $attachments ?? null,
        ]);

        try {
            broadcast(new Reply($sender, $reply, $conversation));
        } catch (\Exception $e) {
            return back();
        }

        return back();
    }

    public function update($uuid)
    {
        $conversation = Conversation::query()
            ->where('uuid', $uuid)
            ->firstOrFail();
        if (request()->has('blocked')) {
            if (is_null($conversation->blocked_by)) {
                $conversation->blocked_by = auth()->id();
            } elseif ($conversation->blocked_by == auth()->id()) {
                $conversation->blocked_by = null;
            }
            $conversation->save();
        }
        if (request()->has('delete') && is_null($conversation->deleted_by)) {
            $conversation->deleted_by = auth()->id();
            $conversation->save();
            return back();
        }
        if (request()->has('delete') && $conversation->deleted_by) {
            $conversation->delete();
            return to_route('user.message.index');
        }
    }
}
