<?php

namespace App\Http\Controllers\Admin;

use App\Traits\Uploader;
use App\Models\AiTemplate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAiTemplateRequest;
use App\Http\Requests\UpdateAiTemplateRequest;

class AiTemplateController extends Controller
{
    use Uploader;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-plus"></i>&nbsp' . __('Add New'),
                'url' => route('admin.ai-templates.create'),
            ]
        ];

        $totalTemplates = AiTemplate::count();
        $activeTemplates = AiTemplate::where('status','active')->count();
        $inActiveTemplates = AiTemplate::where('status','!=','active')->count();

        $templates = AiTemplate::paginate(30);
        return inertia('Admin/AiTemplates/Index', [
            'templates' => $templates,
            'totalTemplates' => $totalTemplates,
            'activeTemplates' => $activeTemplates,
            'inActiveTemplates' => $inActiveTemplates,
            'buttons' => $buttons,
            'segments' => $segments,
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
                'name' => '<i class="fa fa-list"></i>&nbsp' . __('Back'),
                'url' => route('admin.ai-templates.index'),
            ]
        ];

        return inertia('Admin/AiTemplates/Create', [
            'buttons' => $buttons,
            'segments' => $segments,
            'aiModels' => config('openai.ai_models',[]),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAiTemplateRequest $request)
    {
        AiTemplate::create($request->validated()); // note: files are auto uploaded via caster
        return to_route('admin.ai-templates.index');
    }

    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AiTemplate $aiTemplate)
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="fa fa-list"></i>&nbsp' . __('Back'),
                'url' => route('admin.ai-templates.index'),
            ]
        ];

        return inertia('Admin/AiTemplates/Edit', [
            'buttons' => $buttons,
            'segments' => $segments,
            'template' => $aiTemplate,
            'aiModels' => config('openai.ai_models',[]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAiTemplateRequest $request, AiTemplate $aiTemplate)
    {
        $aiTemplate->update($request->validated()); // note: files are auto uploaded via caster
        return to_route('admin.ai-templates.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AiTemplate $aiTemplate)
    {
        $aiTemplate->delete();
        return to_route('admin.ai-templates.index');
    }
}
