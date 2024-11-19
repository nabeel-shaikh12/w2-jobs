<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Opening;
use App\Services\Notify;
use App\Traits\Uploader;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CompanyReview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserPanelController extends Controller
{
    use Uploader;

    public function accountSetting()
    {
        $user = Auth::user();
        return Inertia::render('User/Settings', compact('user'));
    }

    public function accountSettingUpdate(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [request()->user()->provider_id ? 'nullable' : 'required', 'email', 'max:255', 'unique:users,email,' . request()->user()->id],
            'phone' => [request()->user()->provider_id ? 'nullable' : 'required', 'string', 'unique:users,phone,' . request()->user()->id],
            'current_password' => [(request()->user()->provider_id ? 'nullable' : 'required'), 'current_password'],
        ]);

        /**
         * @var \App\Models\User
         */
        $user = auth()->user();

        

        $user->update([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
        ]);

        

        return back();
    }

    public function changePassword()
    {
        return Inertia::render('User/SettingPassChange');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'confirmed'],
        ]);

        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $user->password = Hash::make($request->get('new_password'));
        $user->save();

        return back();
    }

    public function editProfile()
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        return Inertia::render('User/Profile', compact('user'));
    }

    public function savedJobs()
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $jobs = $user->jobBookmarks()
            ->with(['user:id,username,avatar,name', 'categories:id,slug,title'])
            ->when(request('order_by') == 'asc', fn ($q) => $q->latest())
            ->when(request('order_by') == 'desc', fn ($q) => $q->oldest())
            ->paginate(10);
        return Inertia::render('User/SavedJob', compact('jobs'));
    }

    public function appliedJob()
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $jobs = $user->appliedJobs()
            ->with(['user:id,username,avatar,name', 'categories:id,slug,title'])
            ->when(request('order_by') == 'asc', fn ($q) => $q->latest())
            ->when(request('order_by') == 'desc', fn ($q) => $q->oldest())
            ->withPivot(['meta', 'seen_at', 'is_hired'])
            ->paginate(10)->through(function ($item) {
                $item->is_reviewed = CompanyReview::query()
                    ->where('user_id', auth()->id())
                    ->where('job_id', $item->id)
                    ->where('company_id', $item->user_id)
                    ->count();
                return $item;
            });

        return Inertia::render('User/AppliedJob', compact('jobs'));
    }

    public function jobAlert()
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $jobs = Opening::with(['categories', 'service.jobs:id,category_id'])
            ->active()
            ->where('category_id', $user->category_id)
            ->when(request('order_by') == 'asc', fn ($q) => $q->latest())
            ->when(request('order_by') == 'desc', fn ($q) => $q->oldest())
            ->paginate(10);

        return Inertia::render('User/JobAlert', compact('jobs'));
    }

    public function jobDestroy($id)
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $apply = $user->appliedJobs()->withPivot('created_at')->findOrFail($id);

        if (Carbon::make($apply->pivot->created_at)->isToday()) {
            $user->appliedJobs()->detach($id);
        }

        return back();
    }

    // destroy the user | soft deleted
    public function destroy()
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();
        $user->deleted_at = now();
        $user->save();

        Auth::logout();

        Notify::success(__('Account has been deleted successfully'));
        return Inertia::location(url('/'));
    }

    public function userNotifications()
    {
        return
            request()
            ->user()
            ->hasMany(Notification::class)
            ->where('is_admin', 1)
            ->limit(5)
            ->get()->map(function ($item) {
                $item->title_short = Str::limit($item->title, 30, '...');
                $item->comment_short = Str::limit($item->comment, 35, '...');
                return $item;
            });
    }

    public function userNotificationsRead(Notification $notification)
    {
        $notification->seen = 1;
        $notification->save();
        return response()->json([
            'success' => true,
        ]);
    }

    public function reviews()
    {
        /**
         * @var \App\Models\User
         */
        $user = auth()->user();

        $viewData['reviews'] = $user->candidateReviews()
            ->with('company', 'job')
            ->orderBy('created_at', request()->sort ?? 'desc')
            ->paginate();

        return inertia('User/Reviews', $viewData);
    }
}
