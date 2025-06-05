<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRoleIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        //return $next($request);
        // Check if the user is authenticated and has either ADMIN or SUPER_ADMIN role
        if (auth()->check() && (auth()->user()->role === "ADMIN" || auth()->user()->role === "SUPER_ADMIN")) {
            return $next($request);
        }

        // Optionally, redirect or abort if the user doesn't have the required role
        return redirect('/machines')->with('error', 'You do not have access to this resource.');
    }
}
