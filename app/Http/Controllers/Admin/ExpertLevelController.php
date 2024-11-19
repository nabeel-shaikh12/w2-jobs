<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\ExpertLevel;
use App\Http\Controllers\Controller;

class ExpertLevelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:expert-levels');
    }
    
    public function index()
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Add New'),
                'url' => '#',
                'target' => '#addNewExpertLevelDrawer',
            ]
        ];
        $expertLevels = ExpertLevel::all();
        return Inertia::render('Admin/ExpertLevels/Index', compact('segments', 'buttons', 'expertLevels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:50'],
        ]);

        ExpertLevel::create($request->only(['title']));

        return back();
    }

    public function update(Request $request, ExpertLevel $expertLevel)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:50'],
        ]);

        $expertLevel->update($request->only(['title']));

        return back();
    }

    public function destroy(ExpertLevel $expertLevel)
    {
        $expertLevel->delete();
        return back();
    }
}
