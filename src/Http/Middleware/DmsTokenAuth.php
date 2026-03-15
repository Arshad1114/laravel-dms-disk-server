<?php

namespace Arshad1114\DmsDiskServer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DmsTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $validToken = config('dms-disk-server.token');

        if (empty($validToken)) {
            abort(500, 'DMS_SERVER_TOKEN is not set in your .env file.');
        }

        $incoming = $request->bearerToken() ?? $request->header('X-DMS-Token');

        if (! $incoming || ! hash_equals($validToken, $incoming)) {
            abort(401, 'Unauthorized. Invalid or missing DMS token.');
        }

        return $next($request);
    }
}
