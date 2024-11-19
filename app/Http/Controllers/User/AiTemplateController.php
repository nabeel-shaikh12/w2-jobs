<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AiTemplate;
use App\Models\Gateway;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiTemplateController extends Controller
{
    public function index()
    {
        $user = User::query()->findOrFail(auth()->id());
        $templates = AiTemplate::query()
            ->where('type', 'candidate')
            ->withCount(['users as isBookmarked' => function ($query) {
                $query->where('user_id', auth()->id());
            }])
            ->latest()
            ->orderBy('isBookmarked', 'asc')
            ->get();
        return Inertia::render('User/AiTemplate/Index', [
            'templates' => $templates,
            'credits' => auth()->user()->credits,
            'bookmarked' => $user->aiTemplates()->select('ai_templates.id')->get(),
        ]);
    }
    public function show($uuid)
    {
        $template = AiTemplate::where('uuid', $uuid)->firstOrFail();
        $gateways = Gateway::where('status', 1)->select('id', 'name', 'currency', 'logo', 'charge', 'multiply', 'min_amount', 'max_amount', 'is_auto', 'image_accept', 'test_mode', 'status', 'phone_required', 'comment')->get();
        $languages = json_decode(file_get_contents(base_path('database/json/languages.json')), true);
        return Inertia::render('User/AiTemplate/Show', [
            'template' => $template,
            'credits' => auth()->user()->credits,
            'credit_fee' => get_option('per_credit_fee'),
            'gateways' => $gateways,
            'languages' => $languages,
        ]);
    }

    public function bookmark()
    {
        $user = User::query()->findOrFail(auth()->id());

        $user->aiTemplates()->toggle(request('ai_template_id'));
        return to_route('user.ai-template.index');
    }
}
