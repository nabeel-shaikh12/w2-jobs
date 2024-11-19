<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use Inertia\Middleware;
use App\Services\SeoMeta;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    


    public function rootView(Request $request)
    {
        $segments = $request->segments();
        $segment = $segments[0] ?? null;
        $layoutName = $segment == 'admin' ? 'admin' : 'default';
        $layoutPath  = 'layouts.' . $layoutName;
        return $layoutPath;
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        if (request()->is('install/*') || !file_exists(base_path('public/uploads/installed'))) {
            return [];
        }
        
        $primaryData = get_option('primary_data', true);
        $locale = Session::get('locale', env('DEFAULT_LANG', 'en'));
        $menu = Cache::remember(
            'menu_' . $locale,
            env('CACHE_LIFETIME', 1800),
            function () use ($locale) {
                return Menu::where('status', 1)->where('lang', $locale)->oldest()->get();
            }
        );
        $app_name = Cache::remember(
            'app_name_' . $locale,
            env('CACHE_LIFETIME', 1800),
            function () {
                return env('APP_NAME', 'Laravel');
            }
        );

        $permissions = [];
        $notifications = [];

        if (auth()->check()) {
            /**
             * @var \App\Models\User
             */
            $user = auth()->user();

            // permissions
            if ($user->isAdmin()) {
                $permissions = $user->getAllPermissions()->pluck('name');
            }

            // notifications
            $notifications =   Notification::query()
                ->when($user->role != 'admin', fn ($q) => $q->where('user_id', $user->id))
                ->where('is_admin', $user->role == 'admin' ? 1 : 0)
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    $item->title_short = Str::limit($item->title, 30, '...');
                    $item->comment_short = Str::limit($item->comment, 35, '...');
                    return $item;
                }) ?? [];
        }

        $theme_data = get_option('theme_path', true);
        $path = env('APP_DEBUG') ? request('home', $theme_data?->home) : $theme_data?->home ?? "One";
        return array_merge(parent::share($request), [
            'auth' => [
                'isLoggedIn' => auth()->check(),
                'csrf' => csrf_token(),
                'flash' =>  session('flash'),
                'name' =>   auth()->check() ? auth()->user()->name : null,
                'username' =>   auth()->check() ? auth()->user()->username : null,
            ],
            'user' =>   auth()->check() ? auth()->user() : null,
            'sassError' => $request->session()->get('sass_error'),
            'flash' => $request->session()->get('flash', []),
            'languages' => get_option('languages', true),
            'theme' => $path,
            'currency' => get_option('base_currency', true),
            'menus' => $menu,
            'primaryData' => $primaryData,
            'app_name' => $app_name,
            'permissions' => $permissions,
            'notifications' => $notifications,
            'notifyMessages' => fn () => \App\Services\Notify::messages(),
            'newsletter_api' => fn () => env('NEWSLETTER_API_KEY', null),
        ]);
    }
}
