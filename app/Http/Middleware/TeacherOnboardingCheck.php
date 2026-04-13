<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TeacherOnboardingCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->hasRole('Teacher')) {
            $staff = Auth::user()->staff;
            if ($staff && !$staff->onboarding_completed) {
                // If the request is NOT for onboarding APIs, block it
                if (!$request->is('api/onboarding/*') && !$request->is('api/logout')) {
                    return response()->json([
                        'error' => true, 
                        'message' => 'Onboarding (JD & Test) is mandatory before full access.', 
                        'onboarding_required' => true
                    ], 403);
                }
            }
        }
        return $next($request);
    }
}
