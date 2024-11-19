<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Support;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $supports = Support::where('user_id', auth()->id())
            ->withCount('conversations')
            ->when($request->filled('status'), function ($query) {
                $status = match (request('status')) {
                    'open' => 1,
                    'pending' => 2,
                    'closed' => 0,
                    default => 0,
                };
                $query->where('status', $status);
            })
            ->orderBy('created_at', $request->order ?? 'desc')->paginate(20);
        return Inertia::render('User/Support/Index', [
            'supports' => $supports,
        ]);
    }

    public function create()
    {
        return Inertia::render('User/Support/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|max:100|min:10',
            'message' => 'required|max:1000',
        ]);

        $support = new Support;
        $support->user_id = auth()->id();
        $support->subject = $request->subject;
        $support->save();

        $support->conversations()->create([
            'comment'  => $request->message,
            'is_admin' => 0,
            'user_id'  => auth()->id()
        ]);

        return to_route('user.support.index');
    }

    public function show(string $id)
    {
        $support = Support::where('user_id', auth()->id())
            ->with('conversations')->findOrFail($id);

        return Inertia::render('User/Support/Show', [
            'support' => $support,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'message' => 'required|max:1000',
        ]);

        $support = Support::where('user_id', auth()->id())->where('status', 1)->findOrFail($id);

        $support->conversations()->create([
            'comment'  => $request->message,
            'is_admin' => 0,
            'seen' => 0,
            'user_id'  => auth()->id()
        ]);

        return back();
    }
}
