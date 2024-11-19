<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGenerate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GeneratedHistoryController extends Controller
{
    public function index(Request $request)
    {
        $segments = request()->segments();
        $buttons = [];
        $aiGenerated = AiGenerate::query();

        if (!empty($request->search)) {
            if ($request->type == 'email') {
                $aiGenerated = $aiGenerated->whereHas('user', function ($q) {
                    return $q->where('email', request('search'));
                });
            } else {
                $aiGenerated = $aiGenerated->where($request->type, 'LIKE', '%' . $request->search . '%');
            }
        }
        $aiGenerated = $aiGenerated
            ->with(['user:id,name,created_at,role', 'aiTemplate:id,title'])
            ->paginate(10);

        $total = AiGenerate::query()->count();
        $totalCharges = AiGenerate::query()->sum('charge');
        $totalResults = AiGenerate::query()->sum('result');

        $type = $request->type ?? 'email';

        return Inertia::render('Admin/GeneratedHistory/Index', [
            'aiGenerated' => $aiGenerated,
            'buttons' => $buttons,
            'segments' => $segments,
            'total' => $total ?? [],
            'totalCharges' => $totalCharges ?? [],
            'totalResults' => $totalResults ?? [],
            'request' => $request,
            'type' => $type,
        ]);
    }
}
