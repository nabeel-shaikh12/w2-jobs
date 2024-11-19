<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\SeoMeta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): Response
    {
        if (isset($request->id)) {
            $plan = Plan::query()->where('status', 1)
                ->where('id', request()->id)->firstOrFail();
        }
        $seo = get_option('seo_register', true);
        $meta['title'] = $seo->site_name ?? '';
        $meta['site_name'] = $seo->site_name ?? '';
        $meta['description'] = $seo->matadescription ?? '';
        $meta['tags'] = $seo->matatag ?? '';
        $meta['preview'] = asset($seo->preview ?? '');
        $googleClient = env('GOOGLE_CLIENT_ID', null);
        $facebookClient = env('FACEBOOK_CLIENT_ID', null);
        SeoMeta::set($meta);

        return Inertia::render('Auth/Register', [
            'seo' => $meta,
            'googleClient' => $googleClient,
            'facebookClient' => $facebookClient,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'password' => ['required', Rules\Password::defaults()],
            'role' => ['required', 'in:user,employer'],
            'terms' => ['required', 'accepted'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        if ($request->filled('plan_id') && $request->role === 'employer') {
            $plan = Plan::query()->where('status', 1)
                ->where('id', request()->plan_id)->firstOrFail();
            $user->plan = $plan->data;
            $user->plan_id = $plan->id;
            $user->credits = $plan->is_trial == 1 ? $plan->data['ai_credits'] : 0;
            $user->will_expire = $plan->is_trial == 1 ? now()->addDays($plan->trial_days) : null;
            $user->save();

            Auth::login($user);

            if ($user->will_expire === null) {
                if (env('EMAIL_MUST_VERIFIED', true)) {
                    $user->sendEmailVerificationNotification();
                    return to_route('verification.notice');
                 }
                
                return inertia()->location(route('employer.membership.payment', $plan->id));
            }
        }
        Auth::login($user);
        if (env('EMAIL_MUST_VERIFIED', true)) {
            $user->sendEmailVerificationNotification();
            return to_route('verification.notice');
        }
        return inertia()->location(route($user->getDashboardRoute()));
    }
}
