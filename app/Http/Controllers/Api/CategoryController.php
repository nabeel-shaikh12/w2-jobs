<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Opening;
use Illuminate\Database\Eloquent\Builder;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $data = [];

        if ($request->filled('service_id')) {
            $data = Category::query()
                ->where('type', 'job_category')
                ->where('category_id', $request->service_id)
                ->get();
        }

        if ($request->filled('service_slug')) {

            $service = Category::query()
                ->where('type', 'service')
                ->where('slug', $request->service_slug)
                ->first();

            return $data = $service ?  $service->categories()->withCount('jobs')->where('type', 'job_category')->get() : [];
        }

        if ($request->filled('category_slug')) {
            $category = Category::query()->where('slug', $request->category_slug)->first();
            $data = $category ?  $category->categories()->where('type', 'job_tag')->get() : [];
        }

        if ($request->filled('category_id')) {
            $data =  Category::query()
                ->where('type', 'job_tag')
                ->where('category_id', $request->category_id)
                ->get();
        }



        return response()->json($data);
    }

    function homeCategories()
    {
        return Category::query()
            ->where('type', 'job_category')
            ->active()
            ->withCount('jobs')
            ->get();
    }

    function featuredCategories()
    {
        return Category::query()
            ->where(['type' => 'job_category', 'is_featured' => true])
            ->active()
            ->limit(5)
            ->get();
    }

    function homeCategoriesByServiceID(Category $service)
    {
        return $service->categories()->active()->get(['id', 'title']);
    }


    function homeServices()
    {
        return Category::query()->where('type', 'service')->active()->get();
    }


    function homeExperts()
    {
        return User::where('role', 'user')
            ->with('service:id,title,slug')
            ->withCount(['profileVisits' => function ($query) {
                return $query->orderBy('profile_visits_count');
            }])
            ->limit(8)
            ->get()
            ->map(function (User $user) {
                return [
                    'visits' => $user->profile_visits_count,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                    'designation' => $user->service?->title ?? '-',
                ];
            });
    }

    function features()
    {
        return Post::where(['type' => 'feature', 'featured' => true, 'status' => 1])
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
    }

    function homeFaqs()
    {
        return Post::with('excerpt:post_id,value')
            ->where(['type' => 'faq', 'featured' => true, 'status' => 1])
            ->get(['id', 'title',]);
    }

    function brands()
    {
        return Category::whereType('brand')->limit(10)->get(['slug as preview']);
    }

    function blogs()
    {
        return Post::with([
            'preview',
            'categories:title',
            'meta' => function ($query) {
                $query->where('key', 'short_description')->select(['post_id', 'value']);
            }
        ])
            ->where('status', 1)->where('type', 'blog')
            ->get();
    }

    function homeCompanies()
    {
        return User::query()
            ->active()
            ->employer()
            ->whereHas('openJobs')
            ->with(['countries', 'states'])
            ->limit(10)
            ->get()
            ->map(function ($user) {

                $user->location = collect([
                    $user->states->value('name'),
                    $user->countries->value('name'),
                ])->filter()->join(', ');

                return $user;
            });
    }

    function feedbacks()
    {
        $rattings = [
            'Not Bad',
            'Nice',
            'Good',
            'Excellent',
            'Awesome',
        ];

        return Post::where('type', 'testimonial')->with('excerpt', 'preview','banner')->latest()->get()
            ->map(fn ($item) => [
                'company_logo' => $item->preview?->value,
                'banner_logo' => $item->banner?->value,
                'review' => $item->excerpt?->value,
                'author' => $item->title,
                'ratting_point' => intval($item->lang),
                'rating_type' => $rattings[($item->lang ?? 0) - 1] ?? '',
                'author_designation' => '',
            ]);
    }
    function homeJobs()
    {
        return Opening::with(['categories', 'service', 'user:id,name,avatar,created_at'])->limit(5)->get();
    }
}
