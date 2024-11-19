<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\AiGenerate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GeneratedHistoryController extends Controller
{
    public function index(Request $request)
    {
        $aiGenerated = AiGenerate::query();
        $aiGenerated = $aiGenerated
            ->where('user_id', auth()->id())
            ->with(['aiTemplate:id,title'])
            ->orderBy('created_at', in_array($request->order, ['desc', 'asc']) ? $request->order : 'desc')
            ->paginate(10);

        $total = AiGenerate::query()->where('user_id', auth()->id())->count();
        $totalCharges = AiGenerate::query()->where('user_id', auth()->id())->sum('charge');
        $totalResults = AiGenerate::query()->where('user_id', auth()->id())->sum('result');

        return Inertia::render('Employer/GeneratedHistory/Index', [
            'aiGenerated' => $aiGenerated,
            'total' => $total ?? [],
            'totalCharges' => $totalCharges ?? [],
            'totalResults' => $totalResults ?? [],
            'request' => $request,
        ]);
    }

    public function edit($uuid)
    {
        $aiGenerated = AiGenerate::query()
            ->where('user_id', auth()->id())
            ->where('uuid', $uuid)
            ->with(['aiTemplate:id,title'])
            ->firstOrFail();

        return Inertia::render('Employer/GeneratedHistory/Edit', [
            'aiGenerated' => $aiGenerated,
        ]);
    }

    public function update(Request $request, $uuid)
    {
        $request->validate([
            'content' => 'required|string',
        ]);
        $aiGenerated = AiGenerate::query()
            ->where('user_id', auth()->id())
            ->where('uuid', $uuid)
            ->firstOrFail();

        $aiGenerated->update([
            'content' => $request->content,
        ]);

        return redirect()->route('employer.ai-generated-history.index');
    }
}
