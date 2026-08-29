<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        if (Session::has('user_id')) {
            $role = Session::get('user_role');

            if ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('borrower.dashboard');
        }

        return $next($request);
    }
}