<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SaasMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (
            $request->is('employer/memberships*')
            || $request->is('employer/dashboard*')
            || $request->is('employer/support*')
            || $request->is('employer/kyc*')
            || $request->is('employer/profile*')
            || $request->is('employer/account-settings*')
            || $request->is('employer/make-subscribe/*')
            || request()->is('employer/company-info')
        ) {
            return $next($request);
        }



        if (Auth::user()->will_expire == null) {
            Session::flash('flash', [
                'saas_error' => __('Your subscription payment is not completed')
            ]);
            $redirect_url = Auth::user()->plan_id == null ? '/employer/memberships' : '/employer/memberships/payment/' . Auth::user()->plan_id;

            return inertia()->location($redirect_url);
        }

        if (Auth::user()->will_expire < now()) {

            Session::flash('flash', [
                'saas_error' => __('Your subscription payment was expired please renew the subscription')
            ]);

            return redirect('/employer/dashboard');
        }


        return $next($request);
    }
}
