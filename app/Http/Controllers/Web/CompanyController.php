<?php

namespace App\Http\Controllers\Web;

use Inertia\Inertia;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use App\Services\SeoMeta;
use Illuminate\Http\Request;

class CompanyController extends Controller
{

    public function index(Request $request)
    {
        $companiesQuery = User::ActiveCompany()
            ->select('id', 'meta', 'created_at', 'avatar', 'username', 'is_star', 'category_id')
            ->has('jobs')
            ->when(request()->filled('keyword'), function ($query) {
                $query->where('name', 'like', '%' . request('keyword') . '%');
            })
            ->when(request()->filled('status'), function ($query) {
                $query->orderBy('meta->company->year_of_establishment', request()->status);
            })
            ->when(request()->filled('team'), function ($query) {
                $query->where('meta->company->size', 'like', '%' . request()->team . '%');
            })
            ->with(['categories:id,title,slug', 'countries:id,name', 'states:id,name'])
            ->withCount('jobs');

        if ($request->filled('category')) {
            $companiesQuery->whereHas('service', function ($query) {
                $query->where('type', 'service')->where('slug', request('category'));
            });
        }
        if ($request->filled('country')) {
            $companiesQuery->whereHas('countries', function ($query) {
                $query->where('country_id', request('country'));
            });
        }
        if ($request->filled('state')) {
            $companiesQuery->whereHas('states', function ($query) {
                $query->where('state_id', request('state'));
            });
        }

        $locations = Location::query()->whereNull('location_id')->get();
        if (in_array(request()->sort, ['asc', 'desc'])) {
            $requestSort = request()->sort;
        }
        $companies = $companiesQuery->orderBy('created_at', $requestSort ?? 'desc')
            ->paginate(9)
            ->withQueryString();
        $services = Category::query()
            ->where('type', 'service')
            ->withCount(['companies' => function ($q) {
                return $q->ActiveCompany();
            }])
            ->get();
        $theme_data = get_option('theme_path', true);
        $path = env('APP_DEBUG') ? request('v',  $theme_data?->company_list) :  $theme_data?->company_list ?? "One";

        $seo = SeoMeta::init('seo_companies');

        $fullPath = 'Web/Company/' . $path . '/Index';

        return Inertia::render($fullPath, [
            'companies' => $companies,
            'services' => $services,
            'locations' => $locations,
            'request' => $request,
            'seo' => $seo,
        ]);
    }

    public function show($username)
    {
        $company = User::whereRole('employer')->where('username', $username)
            ->with(
                'service:id,title,slug',
                'companyReviews.author:id,name,avatar',
                'companyReviews.author.countries:id,name',
                'countries:id,name',
                'states:id,name'
            )->firstOrFail();
        $jobs = $company->jobs()->with(['categories', 'user:id,name,created_at,avatar', 'country'])
            ->where('status', 1)
            ->where('live_expire_at', '>', today())
            ->orderBy('created_at', 'desc')
            ->limit(5)->get();

        $meta = $company->meta ?? '';
        $seo['title'] = $meta['company']['name'] ?? '';
        $seo['site_name'] = $meta['company']['name'] ?? '';
        $seo['description'] = $meta['business']['description'] ?? '';

        $seo['preview'] = asset($company->avatar != null ? $company?->avatar : '');;

        return Inertia::render('Web/Company/One/Show', [
            'company' => $company,
            'jobs' => $jobs,
            'seo' => SeoMeta::set($seo),
        ]);
    }
}
