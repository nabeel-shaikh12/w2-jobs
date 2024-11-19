<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class KYCVerifiedMiddleware
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
        if (Auth::check() && !Auth::user()->kyc_verified_at) {
            if (env('KYC_MUST_VERIFIED', true)) {
                $registered_at = Auth::user()->created_at;
                $verified_to_at = Carbon::today()->addDays(env('KYC_VERIFICATION_MIN_DAYS', 100));

                if ($registered_at >= $verified_to_at || !Auth::user()->kyc_verified_at) {
                    return to_route('employer.dashboard');
                }
            }
        }
        return $next($request);
    }
}
