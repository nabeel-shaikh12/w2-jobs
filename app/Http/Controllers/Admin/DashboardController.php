<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\Jobbookmark;
use App\Models\KYCRequest;
use App\Models\Opening;
use App\Models\Plan;
use App\Models\Userjob;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalOrders = Order::count();
        $totalSales = Order::sum('amount');

        $totalJobs = Opening::query()->where('status', 1)->count();
        $totalEmployer = User::query()->where('status', 1)
            ->whereRole('employer')->count();
        $totalCandidate = User::query()->where('status', 1)->whereRole('user')->count();
        $totalAppliedJobs = Userjob::count();
        $totalKYCRequest = KYCRequest::query()->count();
        $totalJobBookmarks = Jobbookmark::query()->count();

        $popularPlans = Plan::whereHas('orders')
            ->withCount('activeuser')
            ->withCount('orders')
            ->orderByDesc('orders_count')
            ->withSum('orders', 'amount')
            ->get()
            ->map(function ($query) {
                return [
                    'name'          => $query->title,
                    'activeuser'    => number_format($query->activeuser_count),
                    'orders_count'  => number_format($query->orders_count),
                    'total_amount'  => amount_format($query->orders_sum_amount, 'icon'),
                ];
            });
        $mostViewedJob = Opening::query()
            ->select('id', 'user_id', 'title', 'total_visits', 'category_id', 'type')
            ->with(['user:id,name,avatar,created_at', 'service:id,title'])
            ->when($request->job === 'today', function ($query) {
                $query->whereHas('visits', function ($q) {
                    $q->whereDate('created_at', Carbon::today());
                });
            })
            ->when($request->job === 'month', function ($query) {
                $query->whereHas('visits', function ($q) {
                    $q->whereYear('created_at', Carbon::now()->year)
                        ->whereMonth('created_at', Carbon::now()->month);
                });
            })
            ->where('status', 1)
            ->orderByDesc('total_visits')->first();

        $mostOrderedPlan = Plan::query()
            ->select('id', 'title', 'price', 'days')
            ->where('status', 1)
            ->when($request->plan === 'today', function ($query) {
                $query->whereHas('orders', function ($q) {
                    $q->whereDate('created_at', Carbon::today());
                });
            })
            ->when($request->plan === 'month', function ($query) {
                $query->whereHas('orders', function ($q) {
                    $q->whereYear('created_at', Carbon::now()->year)
                        ->whereMonth('created_at', Carbon::now()->month);
                });
            })
            ->withCount('orders')
            ->orderByDesc('orders_count')
            ->first();
        $recentOrders = Order::whereHas('user')
            ->whereHas('plan')
            ->with('user:id,avatar,name,created_at', 'plan:id,title')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($query) {
                return [
                    'avatar'      => $query->user->avatar ? asset($query->user->avatar) : 'https://ui-avatars.com/api/?name=' . $query->user->name,
                    'name'        => $query->user->name,
                    'plan'        => $query->plan->title,
                    'invoice'     => $query->invoice_no,
                    'amount'      => amount_format($query->amount, 'icon'),
                    'created_at'  => $query->created_at->diffForHumans(),
                    'link'        => url('admin/order/' . $query->id),
                ];
            });

        // recent Jobs, recent kyc request
        $recentJobs = Opening::query()
            ->select('id', 'user_id', 'title', 'total_visits', 'category_id', 'type', 'salary_range', 'salary_type', 'created_at')
            ->with(['user:id,name,avatar,created_at', 'service:id,title'])->take(5)->get();
        $recentKycRequest = KYCRequest::query()
            ->with(['user:id,name,avatar,created_at', 'method:id,title'])
            ->take(5)->get();
        // chart
        $filterBy = request('sales') ?? 'day';
        $dateFormat = match ($filterBy) {
            'year' => "%Y",
            'month' => "%M %Y",
            'week' => "%a",
            default => "%h:00 %p",
        };
        $salesOverview = Order::query()
            ->selectRaw("DATE_FORMAT(orders.created_at,'$dateFormat') as date, SUM(orders.amount) as sales")
            ->when($filterBy == 'day', function ($query) {
                $query->whereDate('created_at', today())
                    ->groupByRaw('HOUR(orders.created_at)');
            })
            ->when($filterBy == 'week', function ($query) {
                $start = now()->startOfWeek(Carbon::SATURDAY);
                $end = now()->endOfWeek(Carbon::FRIDAY);
                $query->whereBetween('orders.created_at', [$start, $end])
                    ->groupByRaw('DAY(orders.created_at)');
            })
            ->when($filterBy == 'month', function ($query) {
                $query->whereYear('created_at', now()->year)
                    ->groupByRaw('MONTH(orders.created_at)');
            })
            ->when($filterBy == 'year', function ($query) {
                $query->groupByRaw('YEAR(orders.created_at)');
            })
            ->orderBy('orders.created_at', 'asc')
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'totalOrders' => $totalOrders,
            'totalSales' => amount_format($totalSales, ''),
            'totalJobs' => $totalJobs,
            'totalEmployer' => $totalEmployer,
            'totalCandidate' => $totalCandidate,
            'totalAppliedJobs' => $totalAppliedJobs,
            'totalKYCRequest' => $totalKYCRequest,
            'totalJobBookmarks' => $totalJobBookmarks,
            'mostViewedJob' => $mostViewedJob,
            'mostOrderedPlan' => $mostOrderedPlan,
            'popularPlans' => $popularPlans,
            'recentOrders' => $recentOrders,
            'recentJobs' => $recentJobs,
            'recentKycRequest' => $recentKycRequest,
            'salesOverview' => $salesOverview,
            'request' => $request,
        ]);
    }
}
