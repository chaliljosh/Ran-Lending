<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Stops the browser from serving a cached snapshot of authenticated
 * pages (admin/borrower dashboard, etc.) via the back/forward button
 * after logout.
 *
 * Without this, pressing "back" right after logging out can show a
 * stale picture of the last page the browser rendered — it LOOKS
 * logged in, but it's not a live page. Any real request from that
 * cached page (clicking a link, submitting a form) still hits the
 * server, which correctly bounces to /login since the session is
 * gone. That's exactly the reported behavior: back button shows the
 * old dashboard, but clicking "Apply for a Loan" on it redirects to
 * login anyway — the visible page was just cached, not a real
 * session.
 *
 * Applying this middleware forces the browser to always re-fetch
 * these pages from the server instead of the back-forward cache, so
 * a logged-out user sees the real (redirected) page immediately.
 */
class PreventBackHistoryCache
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}