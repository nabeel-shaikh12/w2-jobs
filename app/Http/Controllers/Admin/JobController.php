<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Opening;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobController extends Controller
{
    use Uploader;
    public function __construct()
    {
        $this->middleware('permission:jobs');
    }

    public function index(Request $request)
    {
        $jobs = Opening::query();

        if ($request->search !== null && $request->search !== '') {
            $jobs = match ($request->type) {
                'name', 'email' => $jobs->whereHas('user', fn ($q) => $q->where($request->type, $request->search)),
                'status' => $jobs->where($request->type, $request->search),
                'category' => $jobs->whereHas('categories', fn ($q) => $q->where('title', 'LIKE', '%' . $request->search . '%')),
                'service' => $jobs->whereHas('service', fn ($q) => $q->where('title', 'LIKE', '%' . $request->search . '%')),
                default => $jobs->where($request->type, 'LIKE', '%' . $request->search . '%'),
            };
        }
        $jobs = $jobs->with(['categories', 'service', 'user:id,name,avatar,created_at'])->latest()->paginate(10);
        $totalJobs = Opening::count();
        $pendingJobs = Opening::where('status', 2)->count();
        $activeJobs = Opening::where('status', 1)->count();
        $inActiveJobs = Opening::where('status', 0)->count();
        $type = $request->type ?? 'email';
        return Inertia::render('Admin/Job/Index', [
            'jobs' => $jobs,
            'totalJobs' => $totalJobs,
            'activeJobs' => $activeJobs,
            'pendingJobs' => $pendingJobs,
            'inActiveJobs' => $inActiveJobs,
            'request' => $request,
            'type' => $type
        ]);
    }

    public function show($id)
    {
        $segments = request()->segments();
        $buttons = [
            [
                'name' => __('Back'),
                'url' => '/admin/job'
            ],

        ];

        $job = Opening::query()
            ->with(['categories', 'service', 'tags', 'user:id,name,avatar,created_at,meta', 'country', 'state'])
            ->findOrFail($id);

        return Inertia::render('Admin/Job/Show', [
            'job' => $job,
            'buttons' => $buttons,
            'segments' => $segments
        ]);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'featured_expire_at' => ['nullable', 'date'],
            'live_expire_at' => ['nullable', 'date'],
        ]);

        $job = Opening::with(['user'])->findOrFail($id);
        if ($request->status == 1 && $job->status != 1) {
            $live_job_for_days = now()->addDays($job->user->plan['live_job_for_days']);
        }
        $job->update([
            'status' => $request->status,
            'featured_expire_at' => $request->featured_expire_at,
            'live_expire_at' => $live_job_for_days ?? $request->live_expire_at ?? null,
        ]);

        return redirect()->back();
    }

    public function destroy($id)
    {
        $opening = Opening::findOrFail($id);
        $this->removeFile($opening->attachment);
        $opening->delete();
        return back();
    }
}
