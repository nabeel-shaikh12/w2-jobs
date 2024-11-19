<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Opening;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HomeApiController extends Controller
{
    public function index(Request $request)
    {
        $data = [];
        if ($request->filled('service_id')) {
            $data = Category::query()
                ->where('type', 'job_category')
                ->where('category_id', $request->service_id)->get();
        }
        if ($request->filled('category_id')) {
            $data = Category::query()
                ->where('type', 'job_tag')
                ->where('category_id', $request->category_id)->get();
        }
        return response()->json($data);
    }

    public function homeCategories()
    {
        return
            cache_remember('homeCategories',  function () {
                return Category::query()
                    ->where('type', 'job_category')
                    ->active()
                    ->withCount(['jobs' => function ($query) {
                        $query
                            ->where('live_expire_at', '>', today())
                            ->where('status', 1);
                    }])
                    ->get();
            });
    }

    public function menuCategories()
    {
        return
            cache_remember('menuCategories',  function () {
                return Category::query()
                    ->where(['type' => 'job_category', 'is_menu_item' => 1])
                    ->active()
                    ->withCount(['jobs'])
                    ->get();
            });
    }

    public function featuredCategories()
    {
        return cache_remember('featuredCategories', function () {
            return Category::query()
                ->latest()
                ->active()
                ->withCount(['jobs' => function ($q) {
                    $q->where('status', 1)
                        ->where('live_expire_at', '>', today());
                }])
                ->where(['type' => 'job_category', 'is_featured' => 1])
                ->orderBy('jobs_count')
                ->limit(6)
                ->get();
        });
    }

    public function featuredServices()
    {
        return cache_remember('featuredServices', function () {
            return Category::query()
                ->latest()
                ->active()
                ->where(['type' => 'service', 'is_featured' => 1])
                ->limit(6)
                ->get();
        });
    }

    public function menuServices()
    {
        return cache_remember('homeMenuServices', function () {
            return Category::query()
                ->latest()
                ->active()
                ->whereIn('type', ['service', 'job_category'])
                ->where(['is_menu_item' => 1])
                ->withCount([
                    'jobs as jobs_count' => function ($query) {
                        return  $query->where('live_expire_at', '>=', today())->where('status', 1);
                    }
                ])
                ->limit(6)
                ->get();
        });
    }

    public function popularCategories()
    {
        return cache_remember('popularCategories', function () {
            return Category::query()
                ->latest()
                ->active()
                ->withCount('jobs')
                ->where(['type' => 'service'])
                ->orderBy('jobs_count')
                ->limit(3)
                ->get();
        });
    }

    public function homeCategoriesByServiceID(Category $service)
    {
        return $service->categories()->active()->get(['id', 'title']);
    }

    public function homeServices()
    {
        return cache_remember('homeServices', function () {
            return Category::query()->where('type', 'service')->active()->get();
        });
    }

    public function homeExperts()
    {
        return cache_remember('homeExperts', function () {
            return User::where('role', 'user')
                ->with('service:id,title,slug')
                ->where('is_star', 1)
                ->withAvg('candidateReviews', 'ratting')
                ->orderBy('candidate_reviews_avg_ratting', 'desc')
                ->limit(8)
                ->get()
                ->map(function (User $user) {
                    return [
                        'avg_ratting' => $user->candidate_reviews_avg_ratting,
                        'name' => $user->name,
                        'username' => $user->username,
                        'avatar' => $user->avatar,
                        'designation' => $user->service?->title ?? '-',
                    ];
                });
        });
    }

    public function features()
    {
        return cache_remember('features', function () {
            return Post::where(['type' => 'feature', 'featured' => true, 'status' => 1])
                ->latest()
                ->with(['excerpt:post_id,value', 'preview'])
                ->get(['id', 'title'])
                ->map(function ($item) {
                    return [
                        'title' => $item->title,
                        'full_text' => $item->excerpt?->value,
                        'text' => Str::limit($item->excerpt?->value, 120, '...'),
                        'preview' => $item->preview?->value,
                    ];
                });
        });
    }

    public function homeFaqs()
    {
        return Post::with('excerpt:post_id,value')
            ->latest()
            ->where(['type' => 'faq', 'featured' => true, 'status' => 1])
            ->get(['id', 'title']);
    }

    public function brands()
    {
        return Category::whereType('brand')->latest()->limit(20)->get(['slug as preview']);
    }

    public function blogs()
    {
        return Post::with([
            'preview',
            'categories:title',
            'meta' => function ($query) {
                $query->where('key', 'short_description')->select(['post_id', 'value']);
            },
        ])
            ->where('status', 1)
            ->where('type', 'blog')
            ->latest()
            ->limit(3)
            ->get();
    }

    public function homeCompanies()
    {
        return User::query()
            ->active()
            ->where('is_star', 1)
            ->employer()
            ->whereHas('openJobs')
            ->with(['countries', 'states'])
            ->withCount('openJobs')
            ->orderBy('open_jobs_count', 'DESC')
            ->limit(10)
            ->get()
            ->map(function ($user) {

                $user->location = collect([
                    $user->states->value('name'),
                    $user->countries->value('name'),
                ])->filter()->join(', ');

                return [
                    'id' => $user->id,
                    'username' => $user->username,

                    'avatar' => $user->avatar,
                    'name' => $user->name,
                    'location' => $user->location,
                    'open_jobs_count' => $user->open_jobs_count,
                ];
            });
    }

    public function feedbacks()
    {
        $ratings = [
            'Not Bad',
            'Nice',
            'Good',
            'Excellent',
            'Awesome',
        ];

        return Post::where('type', 'testimonial')->with('excerpt', 'preview','banner')->latest()->limit(10)->get()
            ->map(fn ($item) => [
                'company_logo' => $item->preview?->value,
                'banner_logo' => $item->banner?->value,
                'review' => $item->excerpt?->value,
                'author' => $item->title,
                'ratting_point' => intval($item->lang),
                'ratting_type' => $ratings[($item->lang ?? 0) - 1] ?? '',
                'rating_type' => $ratings[($item->lang ?? 0) - 1] ?? '',
                'author_designation' => $item->slug,
            ]);
    }
    public function homeJobs()
    {
        return Opening::with(['categories', 'service', 'user:id,name,avatar,created_at'])
            ->active()
            ->latest()
            ->limit(10)
            ->get();
    }

    public function totalJobsCount()
    {
        return response()->json(
            Opening::query()
                ->where('status', 1)
                ->where('live_expire_at', '>', today())
                ->count()
        );
    }
}
