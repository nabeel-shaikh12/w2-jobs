<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\KycMethod;
use App\Models\KYCRequest;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Str;
class KYCVerificationController extends Controller
{
    public function index()
    {
        $KYCDocuments = KYCRequest::whereUserId(Auth::id())->with('method')->latest()->paginate();

        return Inertia::render('Employer/KYC/Index', compact('KYCDocuments'));
    }

    public function create()
    {
        abort_if(Auth::user()->kyc_verified_at, 403);
        $kyc_methods = KycMethod::whereDoesntHave('users', function (Builder $query) {
            $query->where('user_id', '=', Auth::id());
        })
            ->whereStatus(1)
            ->get();

        return Inertia::render('Employer/KYC/Create', compact('kyc_methods'));
    }

    public function store(Request $request)
    {

        /**
         * @var \App\Models\User
         */
        $user = auth()->user();

        abort_if($user->kyc_verified_at, 403);

        $request->validate([
            'note' => ['nullable', 'string'],
            'method' => ['required', 'exists:kyc_methods,id'],
        ]);

        $method = KycMethod::findOrFail($request->input('method'));

        if ($user->kycMethods()->whereId($method->id)->whereNot('status', 2)->exists()) {
            return back()->with('message', "You're already submitted");
        }

        $data = [];
        foreach ($request->fields as $item) {

            Validator::make(
                $item,
                [
                    'value' => ['required'],
                ],
                [],
                [
                    'value' => $item['label'],
                ]
            )->validate();

            if ($item['type'] == 'file') {
                Validator::make(
                    $item,
                    [
                        'value' => ['required', File::types(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'])->max('5000')],
                    ],
                    [],
                    [
                        'value' => $item['label'],
                    ]
                )->validate();
            }

            $value = $item['value'] ?? '';

            if ($value && is_file($value)) {
                $file = $value;
                $path = '/uploads/' . date('y/m');
                $filename = Str::random(10) . '.' . $file->extension();
                $filepath = $path . '/' . $filename;
                Storage::put($filepath, file_get_contents($value));
                $item['value'] = url($filepath);
            }
            $data[] = $item;
        }

        DB::transaction(function () use ($request, $method, $data) {

            $kyc_request = KYCRequest::create([
                'user_id' => Auth::id(),
                'kyc_method_id' => $request->input('method'),
                'status' => 0,
                'note' => $request->input('note'),
                'data' => $data,
                'fields' => $method->fields,
            ]);

            $method->users()->attach(Auth::id(), ['kyc_request_id' => $kyc_request->id]);

            Notification::create([
                'user_id' => auth()->id(),
                'is_admin' => 1,
                'url' => route('admin.kyc-requests.show', $kyc_request->id),
                'title' => __('New KYC verification request'),
                'comment' => auth()->user()->name . __(' send a kyc verification requests'),

            ]);
        });

        return to_route('employer.kyc.index');
    }

    public function show(KYCRequest $kyc)
    {
        return Inertia::render('Employer/KYC/Show', compact('kyc'));
    }

    public function resubmit(KYCRequest $kyc)
    {
        abort_if(Auth::user()->kyc_verified_at, 403);
        abort_if($kyc->status !== 2, 403);
        return Inertia::render('Employer/KYC/ReSubmit', compact('kyc'));
    }

    public function resubmitUpdate(Request $request, KYCRequest $kyc)
    {
        /**
         * @var \App\Models\User
         */

        abort_if($kyc->status !== 2, 403, __('You\'re already submitted'));

        $request->validate([
            'note' => ['nullable', 'string'],
            'fields' => ['required', 'array'],
        ]);

        $data = [];
        foreach ($request->fields as $item) {
            if ($item['type'] == 'file') {
                Validator::make(
                    $item,
                    [
                        'value' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
                    ],
                    [],
                    [
                        'value' => $item['label'],
                    ]
                )->validate();
            }

            $value = $item['value'];

            if ($value && is_file($value) && $item['type'] == 'file') {
                $file = $value;
                $path = '/uploads/' . date('y/m');
                $filename = Str::random(10) . '.' . $file->extension();
                $filepath = $path . '/' . $filename;
                Storage::put($filepath, file_get_contents($value));
                $item['value'] = $filepath;
            }
            $data[] = $item;
        }

        DB::transaction(function () use ($request, $data, $kyc) {

            $kyc->update([
                'user_id' => Auth::id(),
                'status' => 3,
                'note' => $request->input('note'),
                'data' => $data,
            ]);

            Notification::create([
                'user_id' => auth()->id(),
                'is_admin' => 1,
                'title' => __('KYC verification Resubmit'),
                'url' => url('admin/kyc-requests/' . $kyc->id),
                'comment' => auth()->user()->name . __(' resend a kyc verification request'),
            ]);
        });

        return to_route('employer.kyc.index');
    }
}
