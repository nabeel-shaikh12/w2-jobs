<?php

namespace App\Http\Controllers\Web;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Opening;
use App\Models\Visitor;
use App\Models\Category;
use App\Models\Location;
use App\Mail\JobApplyAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Userjob;
use App\Services\SeoMeta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{

    public function index(Request $request, $slug = null)
    {
        $openingsQuery = Opening::query()
            ->where('status', 1)
            ->where('live_expire_at', '>', today())
            ->when(request()->filled('keyword'), function ($query) {
                $query->where('title', 'like', '%' . request('keyword') . '%');
            })
            ->when(request()->filled('experience'), function ($query) {
                $query->where('experience', request('experience'));
            })
            ->when(request()->filled('job_type'), function ($query) {
                $query->where('type', request('job_type'));
            })
            ->when(request()->filled('currency'), function ($query) {
                $query->where('currency', request('currency'));
            })
            ->when(
                request()->filled('min_salary') && request()->filled('max_salary'),
                function ($query) use ($request) {
                    $query->whereRaw("CAST(SUBSTRING_INDEX(salary_range, '-', 1) AS UNSIGNED) >= ? AND CAST(SUBSTRING_INDEX(salary_range, '-', -1) AS UNSIGNED) <= ?", [$request->min_salary, $request->max_salary]);
                }
            )
            ->when(request()->filled('salary_type'), function ($query) {
                $query->where('salary_type', request('salary_type'));
            })
            ->when(request()->filled('is_remote'), function ($query) {
                $query->where('meta->is_remote', true);
            })->with([
                'country:id,name', 'state:id,name',
                'user:id,avatar,created_at', 'categories:id,title,slug', 'tags:id,title,slug',
            ]);

        if ($slug) {
            $openingsQuery->where(function ($query) use ($slug) {
                $query->whereHas('categories', function ($query) use ($slug) {
                    $query->where('slug', 'like', '%' . $slug . '%');
                })->orWhereHas('service', function ($query) use ($slug) {
                    $query->where('slug', 'like', '%' . $slug . '%');
                });
            });
        }

        if ($request->filled('category')) {
            $openingsQuery->whereHas('categories', function ($query) {
                $query->where('slug', 'like', '%' . request('category') . '%');
            });
        }

        if ($request->filled('service')) {
            $openingsQuery->whereHas('service', function ($query) {
                $query->where('slug', 'like', '%' . request('service') . '%');
            });
        }

        if ($request->filled('tags')) {
            $openingsQuery->whereHas('categories', function ($query) {
                $query->whereIn('id', request('tags'));
            });
        }

        if ($request->filled('country')) {

            $openingsQuery->whereHas('country', function ($query) {
                return  $query->where('country_id', request('country'));
            });
        }
        if ($request->filled('state')) {
            $openingsQuery->whereHas('state', function ($query) {
                return   $query->where('state_id', request('state'));
            });
        }
        if (in_array(request('sort'), ['asc', 'desc'])) {
            $requestOrder = request('sort');
        }
        $openings = $openingsQuery->orderBy('featured_expire_at', $requestOrder ?? 'desc')
            ->latest()
            ->paginate(8)->withQueryString();

        $locations = Location::query()->whereNull('location_id')->get();

        $JobTypes = Opening::selectRaw('type, count(*) as count')
            ->where('status', 1)
            ->groupBy('type')
            ->get();
        $JobExperiences = Opening::selectRaw('experience, count(*) as count')
            ->where('status', 1)
            ->groupBy('experience')
            ->get();

        $services = Category::query()
            ->select('id', 'title', 'slug')
            ->where('type', 'service')
            ->get();

        $categories = Category::query()
            ->select('id', 'title', 'slug')
            ->where('type', 'job_category')
            ->withCount('jobs')
            ->get();

        $tags = Category::query()
            ->select('id', 'title')
            ->where('type', 'job_tag')
            ->get();

        $currencies = collect(json_decode(file_get_contents(base_path('database/json/currencies.json')), true))
            ->values()->toArray();


        $maxSalary = Opening::selectRaw("CAST(SUBSTRING_INDEX(salary_range, '-', -1) AS UNSIGNED) as max_salary")
            ->orderBy('max_salary', 'desc');
        if ($maxSalary->exists()) {
            $maxSalary = $maxSalary->first()
                ->max_salary;
        }
        $theme_data = get_option('theme_path', true);
        $path = env('APP_DEBUG') ? request('v', $theme_data?->job_list?->path) : $theme_data?->job_list?->path ?? "One";

        $type = env('APP_DEBUG') ? request('type', $theme_data?->job_list?->type) : $theme_data?->job_list?->type ?? "One";

        $fullPath = 'Web/Job/' . $path . '/Index' . $type;

        $seo = get_option('seo_job_list', true);
        $meta['title'] = $seo->site_name ?? '';
        $meta['site_name'] = $seo->site_name ?? '';
        $meta['description'] = $seo->matadescription ?? '';
        $meta['tags'] = $seo->matatag ?? '';
        $meta['preview'] = asset($seo->preview ?? '');

        return Inertia::render($fullPath, [
            'openings' => $openings,
            'locations' => $locations,
            'jobTypes' => $JobTypes,
            'JobExperiences' => $JobExperiences,
            'categories' => $categories,
            'tags' => $tags,
            'services' => $services,
            'maxSalary' => $maxSalary ?? 1000,
            'currencies' => $currencies,
            'request' => $request,
            'seo' => SeoMeta::set($meta),
        ]);
    }

    public function show($slug)
    {

        $job = Opening::query()->where('slug', $slug)
            ->where('status', 1)
            ->where('live_expire_at', '>', today())
            ->with(['tags:id,title,slug', 'country', 'state', 'user:id,meta,avatar,created_at,username', 'service:id,title,slug'])
            ->firstOrFail();
        $job->fields = json_decode($job->fields);

        $jobs = $job->all()->filter(function ($item) use ($job) {
            $item->id != $job->id;
        });
        $jobs = Opening::query()
            ->whereNot('id', $job->id)
            ->where('status', 1)
            ->where('live_expire_at', '>', today())
            ->where('user_id', $job->user_id)
            ->with(['tags:id,title', 'country', 'state', 'user:id,meta,avatar,created_at'])
            ->limit(6)->get();

        $alreadyApplied = (bool) (auth()->check() && auth()->user()->appliedJobs()->find($job->id));


        $theme_data = get_option('theme_path', true);

        $path = env('APP_DEBUG') ? request('v', $theme_data?->job_detail) : $theme_data?->job_detail ?? "One";
        $fullPath = 'Web/Job/' . $path . '/Show';

        // visitor
        $visitedJobs = request()->session()->get('visitedJobs', []);
        if (!in_array($job->id, $visitedJobs)) {
            $visitedJobs[] = $job->id;
            Visitor::create([
                'opening_id' => $job->id,
                'ip_address' => request()->ip(),
                'user_id' => auth()->check() ? auth()->id() : null,
            ]);
            $job->increment('total_visits');
            request()->session()->put('visitedJobs', $visitedJobs);
        }

        $seo['title'] = $job->title;
        $seo['site_name'] = $job->title;
        $seo['description'] = $job->short_description ?? '';
        $seo['preview'] = asset($job->user?->avatar != null ? $job->user?->avatar : '');


        return Inertia::render($fullPath, [
            'job' => $job,
            'jobs' => $jobs,
            'alreadyApplied' => $alreadyApplied,
            'seo' => SeoMeta::set($seo),
        ]);
    }

    public function toggleBookmark(Opening $job)
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        if ($user->jobBookmarks()->wherePivot('opening_id', $job->id)->count()) {
            $user->jobBookmarks()->detach($job);
        } else {
            $user->jobBookmarks()->attach($job);
        }
        return back();
    }

    public function apply(Opening $job)
    {
        $seo['title'] = $job->title;
        $seo['site_name'] = $job->title;
        $seo['description'] = $job->short_description ?? '';
        $seo['preview'] = asset($job->user?->avatar != null ? $job->user?->avatar : '');

        SeoMeta::set($seo);
        $job->fields = json_decode($job->fields);

        return Inertia::render('Web/Job/Apply', compact('job', 'seo'));
    }

    public function applyStore(Request $request, Opening $job)
    {
        $isEmailApply = $job->apply_type == 1;

        if ($isEmailApply) {
            $request->validate([
                'email' => ['required', 'email', 'max:200'],
                'message' => ['required', 'string', 'max:255']
            ]);
        }

        // validate Custom fields
        foreach ($request->fields as $item) {
            Validator::make(
                $item,
                [
                    'value' => [
                        'required',
                        $item['type'] == 'email' ? 'email'
                            : ($item['type'] == 'file'
                                ? File::types(['pdf', 'doc', 'docx'])->max(5000)
                                : ($item['type'] == 'number'
                                ? 'numeric'
                                : 'string')),
                    ],
                ],
                [],
                [
                    'value' => $item['label'],
                ]
            )->validate();
        }

        // job application process
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();

        if ($user->appliedJobs()->find($job)) {
            // already Applied
            return to_route('jobs.show', $job);
        }

        if (in_array($job->apply_type, [0, 1])) {

            // store if have files
            $applicationData = [
                'email' => $request->email,
                'message' => $request->message,
                'fields' => []
            ];

            foreach ($request->fields as $item) {
                $value = $item['value'];
                if ($value && is_file($value)) {
                    $file = $value;
                    $path = '/uploads/' . date('y/m') . '/' . str($item['label'])->slug();
                    $filename = $user->username . '-' . str($item['label'])->slug() . '.' . $file->extension();
                    $filepath = $path . '/' . $filename;
                    Storage::put($filepath, file_get_contents($value));
                    $item['value'] = Storage::url($filepath);
                }

                $applicationData['fields'][] = $item;
            }

            DB::transaction(function () use ($job, $user, $applicationData) {

                // apply job
                $user->appliedJobs()->attach($job);

                // applied Job
                $jobApplication = $user->hasMany(Userjob::class)->latest()->first();

                $jobApplication->meta = $applicationData;
                $jobApplication->save();

                Notification::create([
                    'user_id' => $job->user_id,
                    'url' => url('/employer/job-applicants'),
                    'title' => __('New Job Application'),
                    'comment' => __('A candidate has been applied on your job post'),
                    'is_admin' => 1
                ]);
            });

            if ($isEmailApply) {
                // email actions
                $email = json_decode($job->meta)?->apply_type?->value ?? null;
                if ($email) {
                    $this->sendJobAlertToTheEmployer($request, $email);
                }
            }
        }

        return to_route('jobs.show', $job);
    }

    private function sendJobAlertToTheEmployer(Request $request, $email)
    {

        try {
            $info['candidate_name'] = Auth::user()->name;
            $info['candidate_username'] = Auth::user()->username ?? Auth::id();

            env('QUEUE_MAIL')
                ? Mail::to($email)->queue(new JobApplyAlert($info))
                : Mail::to($email)->send(new JobApplyAlert($info));
        } catch (\Exception $e) {
            abort(505, trans("Invalid Email Configuration"));
        }
    }
}
