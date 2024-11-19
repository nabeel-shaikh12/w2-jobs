<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class JobCategoryController extends Controller
{
    use Uploader;

    public function __construct()
    {
        $this->middleware('permission:job-category');
    }

    public function index()
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Create a category'),
                'url' => '#',
                'target' => '#addNewCategoryDrawer',
            ]
        ];
        $categories = Category::whereType('job_category')->with('parent:id,title,slug')
            ->latest()->paginate(10);
        $totalCategories = Category::whereType('job_category')->count();
        $activeCategories = Category::whereType('job_category')->where('status', 1)->count();
        $inActiveCategories = Category::whereType('job_category')->where('status', 0)->count();
        $languages = get_option('languages', true);
        $allServices = Category::whereType('service')->get();

        return Inertia::render('Admin/JobCategory/Index', [
            'categories' => $categories,
            'totalCategories' => $totalCategories,
            'activeCategories' => $activeCategories,
            'inActiveCategories' => $inActiveCategories,
            'languages' => $languages,
            'buttons' => $buttons,
            'segments' => $segments,
            'allServices' => $allServices,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'min:2', 'max:100'],
            'category_id' => ['required', 'exists:categories,id'],
            'preview' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,svg,webp'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,svg,webp']
        ]);

        if ($request->hasFile('preview')) {
            $preview = $this->saveFile($request, 'preview');
        }
        if ($request->hasFile('icon')) {
            $icon = $this->saveFile($request, 'icon');
        }

        Category::create([
            'title' => $request->title,
            'category_id' => $request->category_id,
            'icon' => $icon ?? null,
            'preview' => $preview ?? null,
            'status' => $request->status,
            'is_featured' => $request->is_featured,
            'is_menu_item' => $request->is_menu_item,
            'type' => 'job_category',
            'slug' => Str::slug($request->title),
        ]);

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
       

        $request->validate([
            'category.title' => ['required', 'min:2', 'max:100'],
            'category.category_id' => ['required', 'exists:categories,id'],
            'category.preview' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,svg,webp'],
            'category.icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,svg,webp']
        ]);
        
        if ($request->hasFile('category.preview')) {
            $preview = $this->saveFile($request, 'category.preview');
        }

        if ($request->hasFile('category.icon')) {
            $icon = $this->saveFile($request, 'category.icon');
        }
        
        $category = Category::findOrFail($id);

        $category->update([
            'title' => $request->category['title'],
            'category_id' => $request->category['category_id'],
            'icon' => $icon ??  $category->icon,
            'preview' => $preview ??  $category->preview,
            'status' => $request->category['status'],
            'is_featured' => $request->category['is_featured'],
            'is_menu_item' => $request->category['is_menu_item'],
            'slug' => Str::slug($request->category['title'])
        ]);

        return redirect()->back();
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $this->removeFile($category->preview);
        $category->delete();

        return redirect()->back();
    }
}
