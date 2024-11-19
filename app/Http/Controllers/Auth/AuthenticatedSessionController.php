<?php

namespace App\Http\Controllers\Auth;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Providers\RouteServiceProvider;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Notify;
use App\Services\SeoMeta;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $seo = get_option('seo_login', true);
        $meta['title'] = $seo->site_name ?? '';
        $meta['site_name'] = $seo->site_name ?? '';
        $meta['description'] = $seo->matadescription ?? '';
        $meta['tags'] = $seo->matatag ?? '';
        $meta['preview'] = asset($seo->preview ?? '');
        $googleClient= env('GOOGLE_CLIENT_ID', null) != null ? true : false;
        $facebookClient= env('FACEBOOK_CLIENT_ID', null) != null ? true : false;

        SeoMeta::set($meta);

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'seo' => $meta,
            'googleClient' => $googleClient,
            'facebookClient' => $facebookClient,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        if (auth()->user()->deleted_at) {
            auth()->logout();
            throw ValidationException::withMessages([
                'email' => trans('This account has been temporary inactive, please contact with admin to get back'),
            ]);
        }

        $request->session()->regenerate();
        return inertia()->location(route(Auth::user()->getDashboardRoute()));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return inertia()->location('/');
    }
}
