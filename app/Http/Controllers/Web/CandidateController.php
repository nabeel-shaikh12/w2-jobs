<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ExpertLevel;
use App\Models\Location;
use App\Models\ProfileVisitor;
use App\Models\ResumeDownload;
use App\Models\Qualification;
use App\Models\User;
use App\Services\SeoMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Inertia\Inertia;

class CandidateController extends Controller
{

    public function index()
    {
        // Fetch active candidates
        $candidates = User::query() // Removing ActiveCandidate scope for testing
            ->withCount(['candidateBookmarks as isBookmarked' => function ($query) {
                $query->where('user_id', auth()->id());
            }])
        // $candidates = User::ActiveCandidate()
        // ->withCount(['candidateBookmarks as isBookmarked' => function ($query) {
        //     $query->where('user_id', auth()->id());
        // }])
    
            // Filter candidates based on various criteria
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
    
            // Apply ordering if specified
            ->when(request()->filled('order_by'), function (Builder $query) {
                $query->orderBy('id', request('order_by'));
            })
    
            // Load necessary relationships
            ->with([
                'countries',
                'states',
                'service',
                'tags:id,title',
            ])
    
            // Paginate results to 12 candidates per page and maintain query string
            ->paginate(12)
    
            // Process each candidate to append additional fields
            ->through(function ($candidate) {
                // Construct a location array from state and country data
                $locationArr = [];
                if ($candidate->states?->first()?->name) {
                    $locationArr[] = $candidate->states?->first()?->name;
                }
                if ($candidate->countries?->first()?->name) {
                    $locationArr[] = $candidate->countries?->first()?->name;
                }
    
                // Add location and service information to the candidate
                $candidate->location = $locationArr;
                $candidate->service = $candidate->service?->first()?->title;
                $candidate->tags = $candidate->tags?->pluck('title');
    
                // Add resume to the candidate's data (from the meta field)
                $candidate->resume = $candidate->meta['resume'] ?? null;
    
                return $candidate;
            });
    
        // Fetch the highest salary amount for display
        $highestSalaryAmount = User::query()->candidate()
            ->selectRaw("MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(meta, '$.expected_salary')) AS DECIMAL(8,2))) AS max")
            ->value('max');
    
        // Load data for filters
        $categories = Category::all();
        $services = $categories->where('type', 'service')->values();
        $categories = $categories->where('type', 'job_category')->values();
        $countries = Location::whereNull('location_id')->get();
        $expertLevels = ExpertLevel::all();
        $qualifications = Qualification::all();
    
        // Load available currencies
        $currencies = collect(json_decode(file_get_contents(base_path('database/json/currencies.json')), true))
            ->values()->toArray();
    
        // Prepare theme data for Inertia rendering
        $theme_data = get_option('theme_path', true);
        $path = env('APP_DEBUG') ? request('v', $theme_data?->candidate_list) : $theme_data?->candidate_list ?? "One";
        $fullPath = 'Web/Candidate/' . $path . '/Index';
        $seo = SeoMeta::init('seo_candidate_list');
    
        // Render the view with all the required data
        return Inertia::render($fullPath, compact('candidates', 'services', 'categories', 'countries', 'qualifications', 'expertLevels', 'seo', 'currencies', 'highestSalaryAmount'));
    }
    
    public function show(String $username)
    {
        /**
         * @var \App\Models\User $candidate
         */
        $candidate = User::query()->where(is_numeric($username) ? 'id' : 'username', $username)->firstOrFail();
        $candidate->load([
            'portfolios:id,title,preview,user_id',
            'categories',
            'conversations',
            'candidateReviews',
            'candidateReviews.company',
            'candidateReviews.company.countries'
        ])
            ->loadAvg('candidateReviews', 'ratting');

        $candidate->country = $candidate->countries()->latest()->first()?->name;
        $candidate->state = $candidate->states()->latest()->first()?->name;
        $candidate->skills = $candidate->tags()->get(['id', 'title', 'slug']);
        $candidate->serviceName = $candidate->service()->first()?->title;
        $candidate->isBookmarked = $candidate->candidateBookmarks()->count();
        $candidate->educations = $candidate->educationQualifications()->pluck('meta')->map(function ($item) {
            $item['degree'] = Qualification::find($item['degree'])?->title ?? '';
            return $item;
        });

        $candidate->mgs_blocked = boolval($candidate->conversations()->value('blocked_by'));

        $currentEduQua = $candidate->educations?->firstWhere('is_current', true);
        $candidate->currentEducationDegree = $currentEduQua && $currentEduQua['degree'] ? $currentEduQua['degree'] : $candidate->educations?->value('degree');
        $theme_data = get_option('theme_path', true);
        $path = env('APP_DEBUG') ? request('v', $theme_data?->candidate_detail) : $theme_data?->candidate_detail ?? "One";

        $fullPath = 'Web/Candidate/' . $path . '/Show';
        $meta = $candidate->meta ?? '';
        $seo['title'] = $candidate->name ?? '';
        $seo['site_name'] = $candidate->username  ?? '';
        $seo['description'] = $meta['overview'] ? str($meta['overview'])->limit(100) : '';
        $seo['preview'] = asset($candidate->avatar != null ? $candidate?->avatar : '');

        $seo = SeoMeta::set($seo);
        $candidate->hasSocial = boolval(isset($candidate->meta['social']) && count($candidate->meta['social']));

        return Inertia::render($fullPath, compact('candidate', 'seo'));
    }

    public function visit(User $candidate)
    {
        $visitedCandidates = request()->session()->get('visitedCandidates', []);
        if (!in_array($candidate->id, $visitedCandidates)) {
            $visitedCandidates[] = $candidate->id;

            ProfileVisitor::create([
                'user_id' => $candidate->id,
                'visitor_id' => auth()->check() ? auth()->id() : null,
                'ip_address' => request()->ip(),
            ]);

            // $candidate->increment('total_visits');
            request()->session()->put('visitedCandidates', $visitedCandidates);

            return 'visite counted';
        }
        return 'already visited';
    }

    public function toggleBookmark($id)
    {
        $candidate = User::findOrFail($id);
        $user = User::findOrFail(auth()->id());
        if ($user->employerBookmarks()->wherePivot('candidate_id', $candidate->id)->count()) {
            $user->employerBookmarks()->detach($candidate->id);
        } else {
            $opening = [];
            if (request()->filled('opening_id')) {
                $opening = ['opening_id' => request('opening_id')];
            }
            $user->employerBookmarks()->attach($candidate->id, $opening);
        }
        return back();
    }

    public function sendMail(Request $request, User $candidate)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100'],
            'message' => ['required', 'string', 'max:500'],
        ]);

        // email sending process


        return back();
    }
    


    
    public function checkDownloadLimit()
    {
        $employer = auth()->user();
    
        if (!$employer || !$employer->isEmployer()) {
            return response()->json(['canDownload' => false, 'redirectUrl' => route('employer.memberships')], 403);
        }
            $downloadCount = session()->get('resume_download_count_' . $employer->id, 0);
            if ($downloadCount >= 5) {
            return response()->json(['canDownload' => false, 'redirectUrl' => route('employer.memberships')]);
        }
            return response()->json(['canDownload' => true]);
    }
    

}
