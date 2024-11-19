<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Qualification;
use App\Http\Controllers\Controller;

class QualificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:qualifications');
    }

    public function index()
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Add New Qualification'),
                'url' => '#',
                'target' => '#addNewQualificationDrawer',
            ]
        ];
        $qualifications = Qualification::all();
        return Inertia::render('Admin/Qualifications/Index', compact('segments', 'buttons', 'qualifications'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'gpa' => ['required', 'numeric'],
        ]);

        Qualification::create($request->only(['title', 'gpa']));

        return back();
    }

    public function update(Request $request, Qualification $qualification)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'gpa' => ['required', 'numeric'],
        ]);

        $qualification->update($request->only(['title', 'gpa']));

        return back();
    }

    public function destroy(Qualification $qualification)
    {
        $qualification->delete();
        return back();
    }
}
