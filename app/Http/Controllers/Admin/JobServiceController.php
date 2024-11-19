<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Illuminate\Support\Str;

class JobServiceController extends Controller
{
    use Uploader;

    public function __construct()
    {
        $this->middleware('permission:job-service');
    }

    public function index()
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Create a service'),
                'url' => '#',
                'target' => '#addNewCategoryDrawer',
            ]
        ];
        $categories = Category::whereType('service')->latest()->paginate(10);
        $totalCategories = Category::whereType('service')->count();
        $activeCategories = Category::whereType('service')->where('status', 1)->count();
        $inActiveCategories = Category::whereType('service')->where('status', 0)->count();
        $languages = get_option('languages', true);

        return Inertia::render('Admin/JobService/Index', [
            'categories' => $categories,
            'totalCategories' => $totalCategories,
            'activeCategories' => $activeCategories,
            'inActiveCategories' => $inActiveCategories,
            'languages' => $languages,
            'buttons' => $buttons,
            'segments' => $segments,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'min:2', 'max:100'],
            'icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,svg,webp'],
            'preview' => ['required', 'image', 'mimes:png,jpg,jpeg,gif,svg,webp']
        ]);

        if ($request->hasFile('icon')) {
            $icon = $this->saveFile($request, 'icon');
        }

        if ($request->hasFile('preview')) {
            $preview = $this->saveFile($request, 'preview');
        }

        Category::create([
            'title' => $request->title,
            'icon' => $icon ?? null,
            'preview' => $preview ?? null,
            'status' => $request->status,
            'is_featured' => $request->is_featured,
            'is_menu_item' => $request->is_menu_item,
            'type' => 'service',
            'slug' => Str::slug($request->title),
        ]);

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'service.title' => ['required', 'min:2', 'max:100'],
            'service.icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,svg,webp'],
            'service.preview' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,svg,webp']
        ]);

        if ($request->hasFile('service.preview')) {
            $preview = $this->saveFile($request, 'service.preview');
        }

        if ($request->hasFile('service.icon')) {
            $icon = $this->saveFile($request, 'service.icon');
        }

        $category = Category::findOrFail($id);

        $category->update([
            'title' =>  $request->service['title'],
            'status' => $request->service['status'],
            'icon' => $icon ?? $category->icon,
            'preview' => $preview ?? $category->preview,
            'is_featured' => $request->service['is_featured'],
            'is_menu_item' => $request->service['is_menu_item'],
            'slug' => Str::slug($request->service['title']),
        ]);

        Cache::forget('homeMenuServices');

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
