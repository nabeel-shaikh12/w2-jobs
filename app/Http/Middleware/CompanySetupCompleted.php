<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanySetupCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::query()
            ->where('id', auth()->id())
            ->whereNotNull('meta')
            ->whereHas('categories')
            ->whereHas('countries')
            ->exists();
        $infoRoute = request()->is('employer/company-info');
        if (!$user && !$infoRoute) {
            return redirect()->route('employer.company-info');
        }

        if ($user && $infoRoute) {
            return redirect()->route('employer.dashboard');
        }

        return $next($request);
    }
}
