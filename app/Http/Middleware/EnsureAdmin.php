<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!Session::has('user_id') || Session::get('user_role') !== 'admin') {
            return redirect()->route('login');
        }

        // I-verify ulit sa DB kung active pa rin talaga ang account —
        // hindi sapat na naka-log in siya noon; kailangan i-check kada
        // request kung sakaling na-deactivate siya habang may session pa.
        $isActive = DB::table('users')
            ->where('user_id', Session::get('user_id'))
            ->value('is_active');

        if (!$isActive) {
            Session::flush();
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        return $next($request);
    }
}