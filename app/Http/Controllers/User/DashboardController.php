<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ProfileVisitor;
use App\Models\User;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();

        $segments = request()->segments();
        $buttons = [];

        if ($user->will_expire != null) {
            $nextDate = Carbon::now()->addDays(7)->format('Y-m-d');
            if ($user->will_expire <= now()) {
                $sassError = __('Your subscription was expired at ' . Carbon::parse($user->will_expire)->diffForHumans() . ' please renew the subscription');
            } elseif ($user->will_expire <= $nextDate) {
                $sassError = __('Your subscription is ending in ' . Carbon::parse($user->will_expire)->diffForHumans());
            }
        }

        
        if (Session::has('success')) {
            $success = Session::get('success');
        }

        $user->load('appliedJobs.country');

        $appliedJobs = $user->appliedJobs()->with('user:id,name,avatar', 'country:id,name', 'state:id,name')->limit(5)->get()->map(function ($item) {
            $item->address = $item->state?->first()?->name . ', ' . $item->country?->first()?->name;
            return $item;
        });

        $total_visitors = $user->hasMany(Visitor::class)->count();
        $total_shortlisted = $user->candidateBookmarks()->whereNotNull('opening_id')->count();
        $total_bookmarks = $user->candidateBookmarks()->count();
        $total_applied_jobs = $user->appliedJobs()->count();

        $activeChartFilterBtn = request('chart_filter_by') ?? 'day';

        $dateFormat = match ($activeChartFilterBtn) {
            'year' => "%Y",
            'month' => "%M %Y",
            'week' => "%a",
            default => "%h:00 %p",
        };

        $visits = ProfileVisitor::query()
            ->where('user_id', auth()->id())

            ->selectRaw("DATE_FORMAT(profile_visitors.created_at,'$dateFormat') as date, COUNT(profile_visitors.id) as views")
            
            ->when($activeChartFilterBtn == 'day', function ($query) {
                $query->whereDate('created_at', today())
                    ->groupByRaw('HOUR(profile_visitors.created_at)');
            })
            ->when($activeChartFilterBtn == 'week', function ($query) {
                $start = now()->startOfWeek(Carbon::SATURDAY);
                $end = now()->endOfWeek(Carbon::FRIDAY);
                $query->whereBetween('profile_visitors.created_at', [$start, $end])
                    ->groupByRaw('DAY(profile_visitors.created_at)');
            })
            ->when($activeChartFilterBtn == 'month', function ($query) {
                $query->whereYear('created_at', now()->year)
                    ->groupByRaw('MONTH(profile_visitors.created_at)');
            })
            ->when($activeChartFilterBtn == 'year', function ($query) {
                $query->groupByRaw('YEAR(profile_visitors.created_at)');
            })
            ->get();

     
        return Inertia::render('User/Dashboard', [
            'sassError' => $sassError ?? null,
            'buttons' => $buttons,
            'segments' => $segments,
            'user' => $user,
            'success' => $success ?? null,
            'total_visitors' => $total_visitors,
            'total_shortlisted' => $total_shortlisted,
            'total_bookmarks' => $total_bookmarks,
            'total_applied_jobs' => $total_applied_jobs,
            'appliedJobs' => $appliedJobs,
            'activeChartFilterBtn' => $activeChartFilterBtn,
            'visits' => $visits,
        ]);
    }
}
