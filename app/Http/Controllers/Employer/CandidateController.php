<?php
namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ExpertLevel;
use App\Models\Location;
use App\Models\Qualification;
use App\Models\ResumeDownload;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\SeoMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage; // Import the Storage facade

class CandidateController extends Controller
{
    public function index()
    {
        // Log the entire user object
        $user = auth()->user();

        $userId = auth()->id();

        // Log userId
        // Get the user's subscription plan
        $user = User::with('plan')->find($userId);
        $userDownloadCount = ResumeDownload::where('user_id', $userId)->count();

        $data = json_decode($user, true);


        $resumes = $data['plan']['data']['resumes'];
        Log::info('Resume Download Limit: ' . $resumes); // Log resume download limit
        $expiredate = Carbon::parse($data['will_expire']);
        Log::info($expiredate);


        $expirationDate = Carbon::parse($user->will_expire);
        if (!$user->plan || $expirationDate->isPast() || $userDownloadCount >= $resumes) {
            return redirect()->back()->with('error', 'You have reached your resume download limit');
        }


        $candidates = User::query()
            ->withCount(['candidateBookmarks as isBookmarked' => function ($query) {
                $query->where('user_id', auth()->id());
            }])
            ->when(request()->filled('keyword'), function (Builder $query) {
                $keyword = request('keyword');
                $query->where('name', 'LIKE', "%$keyword%")
                    ->orWhere('email', 'LIKE', "%$keyword%");
            })
            ->when(request()->filled('service') || request()->filled('category'), function (Builder $query) {
                $service_id = Category::whereSlug(request()->service)->value('id') ?? 0;
                $category_id = Category::whereSlug(request()->category)->value('id') ?? 0;
                $query->whereIn('category_id', [$service_id, $category_id]);
            })
            ->when(request()->filled('country'), function (Builder $query) {
                $query->where('meta->country_id', request()->country);
            })
            ->when(request()->filled('state'), function (Builder $query) {
                $query->where('meta->state_id', request()->state);
            })
            ->when(request()->filled('expert_level'), function (Builder $query) {
                $query->where('meta->expert_level', request()->expert_level);
            })
            ->when(request()->filled('currency'), function ($query) {
                $query->where('meta->currency', request()->currency);
            })
            ->when(request()->filled('qualification'), function (Builder $query) {
                $query->whereJsonContains('meta->education_qualifications', ['degree' => intval(request()->qualification)]);
            })
            ->when(request()->filled('gender'), function (Builder $query) {
                $query->where('meta->gender', request()->gender);
            })
            ->when(request()->filled('max_salary'), function (Builder $query) {
                $query->whereBetween('meta->expected_salary', [
                    request()->integer('min_salary', 0),
                    request()->integer('max_salary', 0)
                ]);
            })
            ->when(request()->filled('order_by'), function (Builder $query) {
                $query->orderBy('id', request('order_by'));
            })
            ->with(['countries', 'states', 'service', 'tags:id,title'])
            ->paginate(12)
            ->withQueryString()
            ->through(function ($candidate) {
                $locationArr = [];
                if ($candidate->states?->first()?->name) {
                    $locationArr[] = $candidate->states?->first()?->name;
                }
                if ($candidate->countries?->first()?->name) {
                    $locationArr[] = $candidate->countries?->first()?->name;
                }

                $candidate->location = $locationArr;
                $candidate->service = $candidate->service?->first()?->title;
                $candidate->tags = $candidate->tags?->pluck('title');
                $candidate->resume = $candidate->meta['resume'] ?? null;
                return $candidate;
            });

        $highestSalaryAmount = User::query()->candidate()
            ->selectRaw("MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.expected_salary')) AS DECIMAL(8,2))) AS max")
            ->value('max');

        $categories = Category::all();
        $services = $categories->where('type', 'service')->values();
        $categories = $categories->where('type', 'job_category')->values();
        $countries = Location::whereNull('location_id')->get();
        $expertLevels = ExpertLevel::all();
        $qualifications = Qualification::all();

        $currencies = collect(json_decode(file_get_contents(base_path('database/json/currencies.json')), true))
            ->values()->toArray();

        $theme_data = get_option('theme_path', true);
        $path = env('APP_DEBUG') ? request('v', $theme_data?->candidate_list) : $theme_data?->candidate_list ?? "One";
        $fullPath = 'Web/Candidate/' . $path . '/Index';
        $seo = SeoMeta::init('seo_candidate_list');

        return Inertia::render($fullPath, compact('candidates', 'services', 'categories', 'countries', 'qualifications', 'expertLevels', 'seo', 'currencies', 'highestSalaryAmount'));
    }

    public function show(String $username)
    {
        $candidate = User::query()->where(is_numeric($username) ? 'id' : 'username', $username)->firstOrFail();
        $candidate->load([
            'portfolios:id,title,preview,user_id',
            'categories',
            'conversations',
            'candidateReviews',
            'candidateReviews.company',
            'candidateReviews.company.countries'
        ])
        ->loadAvg('candidateReviews', 'rating');

        $meta = $candidate->meta ?? [];
        $seo['title'] = $candidate->name;
        $seo['site_name'] = $candidate->username;
        $seo['description'] = isset($meta['overview']) ? str($meta['overview'])->limit(100) : '';
        $seo['preview'] = $candidate->avatar ? asset($candidate->avatar) : '';

        return Inertia::render('Web/Candidate/Show', compact('candidate', 'seo'));
    }

    public function checkResumeLimit() 
    {
        $user = User::findOrFail(auth()->id());
        
        $hasReachedLimit = false;
        $expirationDate = Carbon::parse($user->will_expire);
    
        if ($user->plan['resumes'] !== -1 && $user->resumes()->count() >= $user->plan['resumes']) {
            $hasReachedLimit = true;
        }
    
        // Check if the plan has expired
        if ($expirationDate->isPast()) {
            $hasReachedLimit = true;
        }
    
        return response()->json([
            'hasReachedLimit' => $hasReachedLimit,
        ]);
    }
    
    public function storeResume(Request $request, User $candidate)
    {
        $user = User::findOrFail(auth()->id());
        $expirationDate = Carbon::parse($user->will_expire);
    
        // Check for resume limit
        if ($user->plan['resumes'] !== -1 && $user->resumes()->count() >= $user->plan['resumes']) {
            return back()->with('error', 'You have reached your resume upload limit. Please upgrade your plan.');
        }
    
        // Validate resume file
        $request->validate([
            'resume' => 'required|mimes:pdf,doc,docx|max:2048', // Max size: 2MB
        ]);
    
        // Remove old resume if exists
        if (isset($candidate->meta['resume'])) {
            Storage::delete('public/resumes/' . $candidate->meta['resume']);
        }
    
        // Store new resume and update meta
        $resumePath = $request->file('resume')->store('public/resumes');
        $candidate->update([
            'meta' => array_merge($candidate->meta ?? [], ['resume' => basename($resumePath)])
        ]);
    
        return back()->with('success', 'Resume updated successfully.');
    }
    
    public function downloadResume($candidateId)
    {
        // Logging the request entry
        Log::info("downloadResume method triggered for user ID: " . auth()->id());
    
        $userId = auth()->id();
        
        // Log userId
        Log::info('User ID: ' . $userId);
        
        // Get the user's subscription plan
        $user = User::with('plan')->find($userId);
        
        // Log the entire user object
        Log::info('User Object: ' . json_encode($user)); // Log user object for debugging
        
        // Check if user is null
        if (!$user) {
            Log::error('User not found for user ID: ' . $userId);
            return response()->json(['message' => 'User not found.'], 404);
        }
        
        // Decode JSON data
        $data = json_decode($user, true);

        // Extract "resumes"
        $resumes = $data['plan']['data']['resumes'];
        Log::info('Resume Download Limit: ' . $resumes); // Log resume download limit
        $expiredate = Carbon::parse($data['will_expire']);
        Log::info($expiredate);
      

        if ($expiredate->isPast()) {
            return back()->with('error', 'You have reached your Resume download limit. Please upgrade your plan!');
        }
    
        // Count the total number of downloads for the current user
        $userDownloadCount = ResumeDownload::where('user_id', $userId)->count();
        Log::info('User Download Count: ' . $userDownloadCount); // Log user download count
    
        // Check if the user has exceeded the download limit
        if ($userDownloadCount >= $resumes) {
            Log::warning('User download limit exceeded. User ID: ' . $userId . ', Total downloads: ' . $userDownloadCount);
            return response()->json(['message' => 'Resume download limit reached. Please renew your subscription.'], 403);
        }
    
        // Assuming candidate ID is used as the resume path
        $resumePath = $candidateId; // The candidate ID itself is being used as the resume path
        Log::info('Resume Path (Candidate ID): ' . $resumePath); // Log resume path
    
// Log the download, with 'downloaded_at' automatically set
            ResumeDownload::create([
                'user_id' => $userId,
                'resume_path' => $resumePath,
                'downloaded_at' => now(), // Storing the timestamp of when the download occurs
            ]);

            Log::info('Resume download logged for user ID: ' . $userId . ' and candidate ID: ' . $resumePath);

            // Always return success indicating the download was recorded
            return response()->json(['downloaded' => true], 200);

    }
    
 
    public function destroyResume(User $candidate)
    {
        if (isset($candidate->meta['resume'])) {
            Storage::delete('public/resumes/' . $candidate->meta['resume']);

            $meta = $candidate->meta;
            unset($meta['resume']);
            $candidate->update(['meta' => $meta]);

            return back()->with('success', 'Resume removed successfully.');
        }

        return back()->with('error', 'No resume found.');
    }
}