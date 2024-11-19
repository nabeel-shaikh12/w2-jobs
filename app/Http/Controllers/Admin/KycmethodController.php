<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycMethod;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class KycmethodController extends Controller
{
    use Uploader;

    public function __construct()
    {
        $this->middleware('permission:kyc-methods');
    }


    public $types = ['text', 'number', 'email', 'tel', 'textarea', 'file'];

    public function index()
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="bx bx-plus"></i>&nbsp&nbsp' . __('Create New Method'),
                'url' => route('admin.kyc-methods.create'),
            ],
        ];

        $all = KycMethod::query()->count();
        $active = KycMethod::query()->where('status', 1)->count();
        $inactive = KycMethod::query()->where('status', 0)->count();

        $kycMethods = KycMethod::latest()->withCount('requests')->paginate(20);
        $KYC_MUST_VERIFIED = env('KYC_MUST_VERIFIED', true);
        return Inertia::render('Admin/KYC/Methods/Index', compact('kycMethods', 'all', 'active', 'inactive', 'segments', 'buttons', 'KYC_MUST_VERIFIED'));
    }

    public function create()
    {

        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="bx bx-list-ol"></i>&nbsp&nbsp' . __('Back to list'),
                'url' => route('admin.kyc-methods.index'),
            ],
        ];

        $types = $this->types;
        return Inertia::render('Admin/KYC/Methods/Create', compact('types', 'segments', 'buttons'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'image' => ['required', 'image'],
            'title' => ['required', 'string'],
            'image_accept' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
            'fields' => ['required', 'array'],
            'fields.*.label' => ['required', 'string'],
            'fields.*.type' => ['required', 'string', Rule::in($this->types)],
        ]);

        KycMethod::create([
            'title' => $request->input('title'),
            'image' => $this->saveFile($request, 'image'),
            'image_accept' => $request->input('image_accept'),
            'status' => $request->input('status'),
            'fields' => $request->input('fields'),
        ]);

        return to_route('admin.kyc-methods.index');
    }

    public function edit(KycMethod $kycMethod)
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="bx bx-list-ol"></i>&nbsp&nbsp' . __('Back to list'),
                'url' => route('admin.kyc-methods.index'),
            ],
        ];

        $types = $this->types;
        return Inertia::render('Admin/KYC/Methods/Edit', compact('kycMethod', 'types', 'segments', 'buttons'));
    }

    public function update(Request $request, KycMethod $kycMethod)
    {

        $request->validate([
            'image' => ['nullable', 'image'],
            'title' => ['required', 'string'],
            'image_accept' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
            'fields' => ['required', 'array'],
            'fields.*.label' => ['required', 'string'],
            'fields.*.type' => ['required', 'string', Rule::in($this->types)],
        ]);

        if ($request->hasFile('image')) {
            $this->removeFile($kycMethod->image);
        }

        $kycMethod->update([
            'title' => $request->input('title'),
            'image' => $request->hasFile('image') ? $this->saveFile($request, 'image') : $kycMethod->image,
            'image_accept' => $request->input('image_accept'),
            'status' => $request->input('status'),
            'fields' => $request->input('fields'),
        ]);

        return to_route('admin.kyc-methods.index');
    }

    public function massDestroy(Request $request)
    {
        foreach ($request->all() as $id) {
            $kycMethod = KycMethod::find($id);
            if ($kycMethod->requests()->count() === 0) {
                if (file_exists($kycMethod->image)) {
                    Storage::delete($kycMethod->image);
                }
                $kycMethod->delete();
            }
        }
        return to_route('admin.kyc-methods.index');
    }
}
