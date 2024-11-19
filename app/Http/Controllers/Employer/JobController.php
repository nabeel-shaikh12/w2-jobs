<?php

namespace App\Http\Controllers\Employer;

use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Opening;
use App\Models\Userjob;
use App\Models\Category;
use App\Models\Location;
use App\Traits\Uploader;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Exports\ApplicantExport;
use Illuminate\Support\Facades\DB;
use App\Models\DescriptionTemplate;
use App\Http\Controllers\Controller;
use App\Models\CandidateReview;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    use Uploader;

    public function index(Request $request)
    {
        $user = User::findOrFail(auth()->id());

        $jobs = $user->jobs()->with(['categories', 'user:id,name,created_at', 'country'])
            ->withCount('appliedApplicants')
            ->when($request->filled('status'), function ($query) {
                $status = match (request('status')) {
                    'active' => 1,
                    'pending' => 2,
                    default => 0,
                };
                $query->where('status', $status);
            })
            ->orderBy('created_at', in_array($request->order, ['desc', 'asc']) ? $request->order : 'desc')
            ->paginate(10);

        return Inertia::render('Employer/Job/Index', [
            'jobs' => $jobs,
            'request' => $request,
        ]);
    }

    public function show($slug)
    {
        $user = User::findOrFail(auth()->id());
        $job = $user->jobs()->where('slug', $slug)->firstOrFail();

        if (request('export')) {
            return Excel::download(new ApplicantExport($job), $slug . '-applicants.csv');
        }

        $applicants = $job->appliedApplicants()
            ->with(['countries', 'states', 'categories', 'tags'])
            ->withCount(['candidateBookmarks as isBookmarked' => function ($query) {
                $query->where('user_id', auth()->id());
            }])
            ->orderBy('created_at', request()->order ?? 'desc')
            ->paginate(10);

        return Inertia::render('Employer/Job/Show', [
            'applicants' => $applicants,
            'job' => $job,
        ]);
    }

    public function create()
    {
        $user = User::findOrFail(auth()->id());
        $services = Category::query()->where('type', 'service')->get();
        $countries = Location::query()->whereNull('location_id')->get();
        $currencies = collect(json_decode(file_get_contents(base_path('database/json/currencies.json')), true))
            ->values()->toArray();
        $shortcodes = array_map(function ($el) {
            return  preg_replace('/\[|\]/', '', $el);
        }, DescriptionTemplate::getShortCodes());
        return Inertia::render('Employer/Job/Create', [
            'services' => $services,
            'countries' => $countries,
            'plan' => $user->plan,
            'job_count' => $user->jobs()->count(),
            'currencies' => $currencies,
            'shortcodes' => Arr::flatten($shortcodes),
            'user' => $user,
        ]);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = User::findOrFail(auth()->id());
        $expirationDate = Carbon::parse($user->will_expire);
        if (!$user->plan) {
            return back()->with('error', 'You have not purchased a plan.');
        }
        if ($expirationDate->isPast()) {
            return back()->with('error', 'You have reached your Job post limit. Please upgrade your plan!');
        }
        if ($user->plan['job_limit'] !== -1 && $user->jobs()->count() >= $user->plan['job_limit']) {
            return back()->with('error', 'You have reached your job post limit. Please upgrade your plan.');
        }
        $currencies = collect(json_decode(file_get_contents(base_path('database/json/currencies.json')), true))
            ->pluck('code')
            ->toArray();

        $isRemote = to_bool($request->meta['is_remote'] ?? false);
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'short_description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'service_id' => 'required|exists:categories,id',
            'country_id' => $isRemote ? '' : 'required|exists:locations,id',
            'state_id' => $isRemote ? '' : 'required|exists:locations,id',
            'type' => 'required|string',
            'salary_type' => 'required|string',
            'currency' => ['required', Rule::in($currencies), 'string'],
            'min_salary' => [Rule::requiredIf(isset($request->max_salary)), 'nullable', 'integer'],
            'max_salary' => [Rule::requiredIf(isset($request->min_salary)), 'nullable', 'integer'],
            'experience' => 'required|string',
            'expertise' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx',
            'address' => $isRemote ? '' : 'required|string',
            'apply_type' => 'required',
            'expired_at' => 'nullable|date|after:today',
            'meta.apply_type.value' => [Rule::requiredIf(in_array($request->apply_type, [1, 2]))],
        ], [
            'category_id' => 'Category is required',
            'service_id' => 'Service is required',
            'country_id' => 'Country is required',
            'state_id' => 'State is required',
            'meta.apply_type.value.required' => 'Apply type is required',
        ]);

        if ($request->filled('fields')) {
            $request->validate([
                'fields.*.label' => 'required|string',
                'fields.*.type' => 'required|in:email,text,number,file',
            ], [
                'fields.*.label.required' => 'Label is required',
                'fields.*.type.required' => 'Type is required',
            ]);
        }

        $jobCount = intval(Opening::where('title', $request->title)->count() ?? 0);
        $slug = Str::of($request->title)->slug()
            ->append($jobCount > 0 ? ($jobCount + 1) : '');

        DB::beginTransaction();
        try {
            if ($request->hasFile('attachment')) {
                $attachment = $this->saveFile($request, 'attachment');
            }
            if ($request->filled('min_salary') && $request->filled('max_salary')) {
                $salaryRange = $request->min_salary . '-' . $request->max_salary;
            }
            $opening = Opening::create([
                'user_id' => auth()->id(),
                'title' => $request->title,
                'slug' => $slug,
                'description' => $request->description,
                'short_description' => $request->short_description,
                'category_id' => $request->service_id,
                'type' => $request->type,
                'salary_type' => $request->salary_type,
                'currency' => $request->currency,
                'salary_range' => $salaryRange ?? null,
                'experience' => $request->experience,
                'expertise' => $request->expertise,
                'attachment' => $attachment ?? null,
                'address' => $request->address,
                'status' => 2,
                'apply_type' => $request->apply_type ?? 0,
                'expired_at' => $request->expired_at ?? null,
                'fields' => is_array($request->fields) ? json_encode($request->fields) : null,
                'meta' => json_encode($request->meta) ?? null,
                'live_expire_at' => now()->addDays($user->plan['live_job_for_days']),
                'featured_expire_at' => now()->addDays(30),
            ]);

            $opening->categories()->sync(array_merge($request->skills, [$request->category_id]));
            if (!$isRemote) {
                $opening->country()->sync([$request->country_id => ['state_id' => $request->state_id]]);
            }

            Notification::create([
                'user_id' => auth()->id(),
                'url' => route('admin.job.show', $opening->id),
                'title' => __('New Job Posted'),
                'comment' => __('An Employer posted a new job'),
                'is_admin' => 1
            ]);

            DB::commit();
            return redirect()->route('employer.jobs.index');
        } catch (\Throwable $th) {
            DB::rollback();

            return response()->json([
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $slug)
    {
        $job = Opening::where('slug', $slug)
            ->where('user_id', Auth::id())
            ->with([
                'categories:id,title,slug,category_id',
                'tags:id,title,slug,category_id',
                'user:id,name,created_at', 'country', 'state'
            ])
            ->firstOrFail();

        $services = Category::query()->where('type', 'service')->get();
        $categories = Category::query()
            ->where('category_id', $job->category_id)
            ->get();
        $tags = Category::query()
            ->where('type', 'job_tag')
            ->where('category_id', $job->categories[0]->id)->get();
        $countries = Location::query()->whereNull('location_id')->get();
        $states = [];
        if ($job->country()->count() > 0) {
            $states = Location::query()->whereNotNull('location_id')
                ->where('location_id', $job->country[0]?->id)->get();
        }
        $currencies = collect(json_decode(file_get_contents(base_path('database/json/currencies.json')), true))
            ->values()->toArray();
        $shortcodes = array_map(function ($el) {
            return  preg_replace('/\[|\]/', '', $el);
        }, DescriptionTemplate::getShortCodes());
        return Inertia::render('Employer/Job/Edit', [
            'job' => $job,
            'services' => $services,
            'categories' => $categories,
            'tags' => $tags,
            'countries' => $countries,
            'states' => $states,
            'plan' => auth()->user()->plan,
            'currencies' => $currencies,
            'shortcodes' => Arr::flatten($shortcodes),
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $job = Opening::where('user_id', Auth::id())->findOrFail($id);
        $currencies = collect(json_decode(file_get_contents(base_path('database/json/currencies.json')), true))
            ->pluck('code')
            ->toArray();
        $isRemote = to_bool($request->job['meta']['is_remote']);
        Validator::make($request->job, [
            'title' => 'required|string',
            'description' => 'required|string',
            'short_description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'service_id' => 'required|exists:categories,id',
            'country_id' => $isRemote ? '' : 'required|exists:locations,id',
            'state_id' => $isRemote ? '' : 'required|exists:locations,id',
            'type' => 'required|string',
            'salary_type' => 'required|string',
            'currency' => ['required', Rule::in($currencies), 'string'],
            'min_salary' => [Rule::requiredIf(isset($request->job['max_salary'])), 'nullable', 'integer'],
            'max_salary' => [Rule::requiredIf(isset($request->job['min_salary'])), 'nullable', 'integer'],
            'experience' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx',
            'address' => $isRemote ? '' : 'required|string',
            'apply_type' => 'required',
            'expired_at' => 'nullable|date|after:today',
            'meta.apply_type.value' => [Rule::requiredIf(in_array($request->job['apply_type'], [1, 2]))],
        ], [
            'category_id' => 'Category is required',
            'service_id' => 'Service is required',
            'country_id' => 'Country is required',
            'state_id' => 'State is required',
            'meta.apply_type.value.required' => 'Apply type is required',
        ])->validate();
        if ($request->filled('job.fields')) {
            Validator::make($request->job, [
                'fields.*.label' => 'required|string',
                'fields.*.type' => 'required|in:email,text,number,file',
            ], [
                'fields.*.label.required' => 'Label is required',
                'fields.*.type.required' => 'Type is required',
            ])->validate();
        }
        $isTitleUnique = Opening::where('title', $request->job['title'])->count();
        if ($job->title != $request->job['title']) {
            $slug = Str::of($request->job['title'])
                ->trim()
                ->lower()
                ->replace(' ', '')
                ->append($isTitleUnique > 0 ? $isTitleUnique . Str::random(2) : '');
        }
        DB::beginTransaction();
        try {
            if ($request->hasFile('job.attachment')) {
                $this->removeFile($job->attachment);
                $attachment = $this->saveFile($request, 'job.attachment');
            }
            if ($request->filled('job.min_salary') && $request->filled('job.max_salary')) {
                $salaryRange = $request->job['min_salary'] . '-' . $request->job['max_salary'];
            }
            $job->update([
                'user_id' => auth()->id(),
                'title' => $request->job['title'],
                'description' => $request->job['description'],
                'short_description' => $request->job['short_description'],
                'category_id' => $request->job['service_id'],
                'type' => $request->job['type'],
                'salary_type' => $request->job['salary_type'],
                'currency' => $request->job['currency'],
                'salary_range' => $salaryRange ?? null,
                'experience' => $request->job['experience'],
                'attachment' => $attachment ?? $job->attachment,
                'address' => $request->job['address'],
                'status' => $job->status == 1 ? 1 : 2,
                'apply_type' => $request->job['apply_type'] ?? 0,
                'expired_at' => $request->job['expired_at'] ?? $job->expired_at,
                'fields' => is_array($request->job['fields']) ? json_encode($request->job['fields']) : null,
                'meta' => json_encode($request->job['meta']) ?? null,
            ]);

            $job->categories()->sync(array_merge($request->job['skills'], [$request->job['category_id']]));

            if ($isRemote) {
                $job->country()->delete();
            } else {
                $job->country()->sync([$request->job['country_id'] => ['state_id' => $request->job['state_id']]]);
            }

            Notification::create([
                'user_id' => auth()->id(),
                'is_admin' => 1,
                'url' => route('admin.job.show', $job->id),
                'title' => __('Job Edited'),
                'comment' => __('An Employer edit an existing job'),

            ]);

            DB::commit();
            return redirect()->route('employer.jobs.index');
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function updateSeenAt(Request $request)
    {
        if ($request->has('apply_id')) {
            $application = Userjob::findOrFail($request->apply_id);
        } else {
            $application = Userjob::where('user_id', $request->candidate_id)
                ->where('opening_id', $request->job_id)->first();
        }

        if ($application && !$application->seen_at) {
            $application->seen_at = now();
            $application->save();
        }

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $opening = Opening::where('user_id', Auth::id())->findOrFail($id);
        $this->removeFile($opening->attachment);
        $opening->delete();
        return back();
    }

    public function applicants()
    {
        $applicants = Userjob::whereHas('opening', function ($query) {
            return $query->where('user_id', auth()->id());
        })
            ->with([
                'user',
                'user.countries',
                'user.states',
                'user.tags',
                'user.categories',
                'user.candidateBookmarks',
                'user.candidateReviews:ratting',
                'opening'
            ])
            ->latest()
            ->paginate(10)->through(function ($item) {
                $item->is_reviewed = CandidateReview::query()
                    ->where('user_id', $item->user_id)
                    ->where('job_id', $item->opening_id)
                    ->where('company_id', auth()->id())
                    ->count();
                $item->user_avg_ratting = number_format($item->user->candidateReviews()->avg('ratting') ?? 0, 2);
                return $item;
            });

        return Inertia::render('Employer/Applicants', compact('applicants'));
    }

    public function reviews()
    {
        /**
         * @var \App\Models\User
         */
        $employer = auth()->user();

        $viewData['reviews'] = $employer->companyReviews()
            ->with('author', 'job')
            ->orderBy('created_at', request()->sort ?? 'desc')
            ->paginate();

        return inertia('Employer/Reviews', $viewData);
    }
}
