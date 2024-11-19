<?php

namespace App\Http\Controllers\Auth;

use Exception;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    public function redirectTo($provider)
    {
        session(['user_role' => request()->r]);
        return Socialite::driver($provider)->redirect();
    }

    public function handleCallback($provider)
    {
        
        try {
            $user = Socialite::driver($provider)->user();

            $findUser = User::where('provider_id', $user->id)
                ->where('provider', $provider)
                ->where('email', $user->email)->first();
            $uuid = (string) Str::uuid();
            if ($findUser) {
                $findUser->update(['password' => Hash::make($uuid . $user->id)]);
                Auth::login($findUser);
                return redirect('/');
            } else {
                $uuid = (string) Str::uuid();

                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'provider_id' => $user->id,
                    'provider' => $provider,
                    'password' => Hash::make($uuid . $user->id),
                    'email_verified_at' => now(),
                    'role' => session('user_role', 'user'),
                ]);
                if ($newUser->role === 'employer') {
                    $plan = Plan::query()->where('status', 1)
                        ->where('is_default', 1);
                    if ($plan->exists()) {
                        $plan = $plan->first();

                        $newUser->plan = $plan->data;
                        $newUser->plan_id = $plan->id;
                        $newUser->credits = $plan->is_trial == 1 ? $plan->data['ai_credits'] : 0;
                        $newUser->will_expire = $plan->is_trial == 1 ? now()->addDays($plan->trial_days) : null;
                        $newUser->save();
                    }

                    if ($user->will_expire === null) {
                        Auth::login($newUser);
                        return inertia()->location(route('employer.membership.payment', $plan->id));
                    }
                }
                Auth::login($newUser);
                return redirect(route($newUser->getDashboardRoute()));
            }
        } catch (Exception $e) {
            
            Session::flash('flash', [
                'saas_error' => 'Login With '.$provider.' Failed'
            ]);

            return redirect(url('/login'));
        }
    }
}
