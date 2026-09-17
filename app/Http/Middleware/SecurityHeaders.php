<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Bawal i-embed ang site sa loob ng iframe (clickjacking protection) —
        // mahalaga ito lalo na para sa admin panel mo.
        $response->headers->set('X-Frame-Options', 'DENY');

        // Pinipigilan ang browser na "guess" ng ibang file type kaysa
        // sa aktwal na ipinapadala nating Content-Type header.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Hindi ipinapadala ang buong URL (kasama query strings) bilang
        // referrer papunta sa ibang site — para lang sa parehong origin
        // ang buong URL na naipapasa.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}