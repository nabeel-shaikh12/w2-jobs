<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CandidateExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CandidateController extends Controller
{
    use Uploader;

    public function __construct()
    {
        $this->middleware('permission:candidates');
    }

    public function index()
    {
        if (request('export')) {
            return Excel::download(new CandidateExport, 'candidates.xlsx');
        }

        $data['segments'] = request()->segments();
        $data['buttons'] = [];
        $data['request'] = request()->all();
        $candidates = User::query()->candidate();

        $data['total_candidates'] = $candidates->clone()->count();
        $data['active_candidates'] = $candidates->clone()->active()->count();
        $data['inactive_candidates'] = $candidates->clone()->inActive()->count();
        $data['verified_candidates'] = $candidates->clone()->whereNotNull('email_verified_at')->count();

        $allowedColumnToSearch = ['name', 'email'];

        $data['candidates'] = $candidates
            ->when(
                request()->filled(['search', 'type']) && in_array(request('type'), $allowedColumnToSearch),
                function ($query) {
                    $query->where(request('type'), "LIKE", '%' . request('search') . '%');
                }
            )
            ->with(['service'])
            ->paginate();

        return Inertia::render('Admin/Candidates/Index', $data);
    }

    public function show(User $candidate)
    {
        $data['segments'] = request()->segments();
        $data['buttons'] = [
            [
                'name' => '<i class="bx bx-list"></i>&nbsp&nbsp' . __('Back to list'),
                'url' => route('admin.candidates.index'),
            ],
        ];

        $data['candidate'] = $candidate;
        $data['total_shortlisted'] = $candidate->candidateBookmarks()->whereNotNull('opening_id')->count();
        $data['total_bookmarks'] = $candidate->candidateBookmarks()->count();
        $data['total_applied_jobs'] = $candidate->appliedJobs()->count();
        $data['appliedJobs'] = $candidate->appliedJobs()->paginate();
        $data['resume'] = $candidate->meta['resume'] ?? null; // Fetch the resume from meta field
        $data['success'] = session('success'); // Include success message if any

        return Inertia::render('Admin/Candidates/Show', $data);
    }

    public function uploadResume(Request $request, User $candidate)
    {
        // Validate the resume upload
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ]);

        // Check if file exists and process upload
        if ($request->hasFile('resume')) {
            // Use the Uploader trait or default Laravel storage to save the resume
            $path = $this->saveFile($request, 'resume'); // Assuming saveFile method handles the storage

            // Update the candidate's meta field with the new resume path
            $candidate->update([
                'meta' => [
                    ...$candidate->meta,
                    'resume' => $path,
                ],
            ]);

            return response()->json(['resume_url' => $path, 'message' => 'Resume uploaded successfully.'], 200);
        }

        return response()->json(['message' => 'No file uploaded.'], 400);
    }

    public function destroyResume(User $candidate)
    {
        // Remove the resume from the meta field
        if ($candidate->meta && isset($candidate->meta['resume'])) {
            $this->removeFile($candidate->meta['resume']); // Assuming removeFile method handles deletion
        }

        $candidate->update(['meta->resume' => null]); // Clear the resume path

        return response()->json(['message' => 'Resume removed successfully.'], 200);
    }
}
