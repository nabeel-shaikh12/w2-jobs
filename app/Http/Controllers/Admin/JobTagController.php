<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class JobTagController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:job-tags');
    }

    public function index()
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Create a skill'),
                'url' => '#',
                'target' => '#addNewCategoryDrawer',
            ]
        ];
        $tags = Category::whereType('job_tag')->with('parent:id,title,slug')
            ->latest()->paginate(10);
        $totalTags = Category::whereType('job_tag')->count();
        $activeTags = Category::whereType('job_tag')->where('status', 1)->count();
        $inActiveTags = Category::whereType('job_tag')->where('status', 0)->count();
        $languages = get_option('languages', true);
        $allCategory = Category::whereType('job_category')->get();

        return Inertia::render('Admin/JobTag/Index', [
            'tags' => $tags,
            'totalTags' => $totalTags,
            'activeTags' => $activeTags,
            'inActiveTags' => $inActiveTags,
            'languages' => $languages,
            'buttons' => $buttons,
            'segments' => $segments,
            'allCategory' => $allCategory,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'min:2', 'max:100'],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        Category::create([
            'title' => $request->title,
            'category_id' => $request->category_id,
            'status' => $request->status,
            'type' => 'job_tag',
            'slug' => Str::slug($request->title),
        ]);

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => ['required', 'min:2', 'max:100'],
            'category_id' => ['required', 'exists:categories,id']
        ]);

        $category = Category::findOrFail($id);

        $category->update([
            'title' => $request->title,
            'category_id' => $request->category_id,
            'status' => $request->status,
            'slug' => Str::slug($request->title)
        ]);

        return redirect()->back();
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return redirect()->back();
    }
}
