<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\DescriptionTemplate;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DescriptionTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Add New'),
                'url' => route('admin.description-templates.create'),
            ]
        ];


        $templates = DescriptionTemplate::paginate()->withQueryString();
        return Inertia::render('Admin/DescriptionTemplates/Index', [
            'templates' => $templates,
            'segments' => $segments,
            'buttons' => $buttons,

            'totalTemplates' => DescriptionTemplate::count(),
            'activeTemplates' => DescriptionTemplate::where('status', 'active')->count(),
            'inactiveTemplates' => DescriptionTemplate::where('status', 'inactive')->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-list"></i>&nbsp' . __('Back to list'),
                'url' => route('admin.description-templates.index'),
            ]
        ];
        $shortcodes = DescriptionTemplate::getShortCodes();
        $categories = Category::whereType('service')->get();

        return Inertia::render('Admin/DescriptionTemplates/Create', [
            'segments' => $segments,
            'buttons' => $buttons,
            'shortcodes' => $shortcodes,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => ['required', 'in:company,job'],
            'body' => ['required'],
            'status' => ['required', 'in:active,inactive'],
            'categories' => ['required', 'array']
        ]);

        DB::transaction(function () use ($request) {
            $template = DescriptionTemplate::create([
                'type' => $request->type,
                'body' => $request->body,
                'status' => $request->status,
                'shortcodes' => DescriptionTemplate::getShortCodesFrom($request->body),
            ]);
            $template->categories()->attach($request->categories);
        });

        return to_route('admin.description-templates.index');
    }

    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DescriptionTemplate $descriptionTemplate)
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Back to list'),
                'url' => route('admin.description-templates.index'),
            ]
        ];

        $descriptionTemplate->load('categories');

        $shortcodes = DescriptionTemplate::getShortCodes();

        $categories = Category::whereType('service')->get();
        return Inertia::render('Admin/DescriptionTemplates/Edit', [
            'segments' => $segments,
            'buttons' => $buttons,
            'template' => $descriptionTemplate,
            'shortcodes' => $shortcodes,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DescriptionTemplate $descriptionTemplate)
    {
        $request->validate([
            'type' => ['required', 'in:company,job'],
            'body' => ['required'],
            'status' => ['required', 'in:active,inactive']
        ]);

        DB::transaction(function () use ($descriptionTemplate, $request) {
            $descriptionTemplate->update([
                'type' => $request->type,
                'body' => $request->body,
                'status' => $request->status,
                'shortcodes' => DescriptionTemplate::getShortCodesFrom($request->body),
            ]);
            $descriptionTemplate->categories()->sync($request->categories);
        });

        return to_route('admin.description-templates.index');
    }

    /**
     * Remove the specified resource        from storage.
     */
    public function destroy(DescriptionTemplate $descriptionTemplate)
    {
        $descriptionTemplate->delete();
        return to_route('admin.description-templates.index');
    }
}
