<?php

// app/Http/Middleware/AuditRequests.php
//
// Layer B (safety net): records every SUCCESSFUL write request (POST / PUT / PATCH / DELETE)
// on the routes it is attached to. It stores the route name, actor, IP and the input FIELD NAMES
// only (never the values), so no sensitive data can leak through it.
//
// If a controller already wrote a detailed entry with AuditLogger::log(), this middleware skips
// that request, so there are no duplicates.
//
// Attach it to the admin and borrower route groups in routes/web.php:
//     Route::middleware([\App\Http\Middleware\AuditRequests::class])->group(function () { ... });

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;

class AuditRequests
{
    /** Route-name patterns that are too noisy to log. */
    private const IGNORE = ['*notification*', '*mark-read*', '*heartbeat*', '*ping*', '*audit-logs*'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $this->record($request, $response);
        } catch (\Throwable $e) {
            Log::error('AuditRequests failed: ' . $e->getMessage());
        }

        return $response;
    }

    private function record(Request $request, $response): void
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        // Already logged in detail by AuditLogger::log()
        if ($request->attributes->get('audit_logged')) {
            return;
        }

        // Failed requests are not logged (only actions that really happened)
        $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;
        if ($status >= 400) {
            return;
        }

        // Redirect back with validation errors = the action did not happen
        if ($request->hasSession()) {
            $errors = $request->session()->get('errors');
            if ($errors instanceof ViewErrorBag && $errors->any()) {
                return;
            }
        }

        $route = $request->route();
        $routeName = $route ? (string) $route->getName() : '';
        if ($routeName !== '' && Str::is(self::IGNORE, $routeName)) {
            return;
        }

        // Route parameters (uuid / ids only, scalars only)
        $params = [];
        if ($route) {
            foreach ($route->parameters() as $key => $value) {
                if (is_scalar($value)) {
                    $params[$key] = (string) $value;
                }
            }
        }

        // Field NAMES only, never the values
        $fields = array_values(array_diff(
            array_merge(array_keys($request->all()), array_keys($request->allFiles())),
            ['_token', '_method']
        ));

        $label = $routeName !== '' ? $routeName : $request->path();

        AuditLogger::log('request.' . Str::limit($label, 70, ''), [
            'description' => $request->method() . ' ' . $label,
            'context' => [
                'route'  => $routeName ?: null,
                'method' => $request->method(),
                'path'   => '/' . ltrim($request->path(), '/'),
                'params' => $params ?: null,
                'fields' => array_slice($fields, 0, 40),
                'status' => $status,
            ],
        ]);
    }
}