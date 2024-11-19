<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CompanyExport;
use App\Http\Controllers\Controller;
use App\Models\Opening;
use App\Models\Plan;
use App\Models\User;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CompanyController extends Controller
{
    use Uploader;

    public function __construct()
    {
        $this->middleware('permission:companies');
    }

    public function index()
    {

        if (request('export')) {
            return Excel::download(new CompanyExport, 'companies.xlsx');
        }

        $data['segments'] = request()->segments();
        $data['buttons'] = [];
        $data['request'] = request()->all();

        $companies = User::query()->with('plan')->employer();

        $data['total_companies'] = $companies->clone()->count();
        $data['active_companies'] = $companies->clone()->active()->count();
        $data['inactive_companies'] = $companies->clone()->inActive()->count();
        $data['verified_companies'] = $companies->clone()->verified()->count();

        $allowedColumnToSearch = ['name', 'email'];

        $data['companies'] = $companies->latest()
            ->when(
                request()->filled(['search', 'type']) && in_array(request('type'), $allowedColumnToSearch),
                function ($query) {
                    $query->where(request()->type, "LIKE", '%' . request('search') . '%');
                }
            )

            ->paginate();

        return Inertia::render('Admin/Companies/Index', $data);
    }

    public function show(User $company)
    {
        $data['segments'] = request()->segments();
        $data['buttons'] = [
            [
                'name' => '<i class="bx bx-list"></i>&nbsp&nbsp' . __('Back to list'),
                'url' => route('admin.companies.index'),
            ],
        ];

        $data['job_posted'] = $company->jobs->count();
        $data['shortlisted'] = 0;
        $data['applications'] = Opening::query()->withCount('appliedApplicants')->get()->sum('applied_applicants_count');
        $data['saved_candidate'] = $company->candidateBookmarks()->count();

        $data['company'] = $company;
        $data['jobs'] = $company->jobs()->paginate();

        return Inertia::render('Admin/Companies/Show', $data);
    }

    public function edit(User $company)
    {

        $segments = request()->segments();
        $buttons = [
            [
                'name' => '<i class="bx bx-list"></i>&nbsp&nbsp' . __('Back to list'),
                'url' => route('admin.companies.index'),
            ],
        ];

        $company->avatar = '';
        $company->status = boolval($company->status);
        $company->email_verified = boolval($company->email_verified_at);
        $company->kyc_verified = boolval($company->kyc_verified_at);
        $company->is_star = boolval($company->is_star);

        $plans = Plan::where('status', 1)->get();

        return Inertia::render('Admin/Companies/Edit', [
            'segments' => $segments,
            'company' => $company,
            'buttons' => $buttons,
            'plans' => $plans,
        ]);
    }

    public function update(Request $request, User $company)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'credits' => ['numeric', 'min:0'],
            'avatar' => ['nullable', 'image'],
            'email' => ['required', 'email', 'max:50', 'unique:users,email,' . $company->id],
            'phone' => ['nullable', 'numeric', 'digits_between:8,16'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'confirmed', 'digits_between:6,16'],
        ]);

        if ($request->plan_id != $company->plan_id) {
            if ($request->plan_id != null) {
                $plan = Plan::findOrFail($request->plan_id);
                $company->plan_id = $plan->id;
                $company->plan = $plan->data;
            } else {
                $company->plan_id = null;
                $company->plan = null;
                $company->will_expire = null;
            }
            $company->save();
        }

        $validatedData['avatar'] =  $this->uploadFile('avatar', $company->avatar);

        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($request->password);
        }

        $validatedData = [
            ...$validatedData,
            'status' => boolval($request->status),
            'email_verified_at' => $request->email_verified ? now()->toDateTimeString() : null,
            'kyc_verified_at' => $request->kyc_verified ? now()->toDateTimeString() : null,
            'is_star' => boolval($request->is_star),
        ];


        $company->fill($validatedData);
        $company->save();

        return to_route('admin.companies.index');
    }

    public function destroy(User $company)
    {
        $company->delete();
        return to_route('admin.companies.index');
    }
}
