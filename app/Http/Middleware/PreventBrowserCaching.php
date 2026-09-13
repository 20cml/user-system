<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticated pages show data that changes on every request (listings,
 * profile, dashboard). Without these headers, some browsers (Safari's
 * back/forward cache in particular) serve a stale copy instead of asking the
 * server again, so changes don't show up until a hard refresh.
 */
class PreventBrowserCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    }
}
