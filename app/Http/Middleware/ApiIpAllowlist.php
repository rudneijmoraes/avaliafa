<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiIpAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientSystem = $request->attributes->get('_client_system');

        if (! $clientSystem) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $clientIp = $request->ip();

        if (! $clientSystem->isIpAllowed($clientIp)) {
            return response()->json([
                'success' => false,
                'message' => 'IP address not allowed',
            ], 403);
        }

        return $next($request);
    }
}
