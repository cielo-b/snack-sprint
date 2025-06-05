<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRoleIsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is authenticated and has the SUPER_ADMIN role
        if (auth()->check() && auth()->user()->role === "SUPER_ADMIN") {
            return $next($request);
        }

        // Redirect if the user doesn't have the required role
        return redirect('/machines')->with('error', 'You do not have access to this resource.');
    }
}
