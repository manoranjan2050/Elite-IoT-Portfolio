<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects the admin API with a static secret token (KAVACH_ADMIN_API_TOKEN).
 * Accepts either "Authorization: Bearer <token>" or "X-Admin-Token: <token>".
 */
class AdminApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.admin_api.token');
        $given = $request->bearerToken() ?? (string) $request->header('X-Admin-Token');

        if ($expected === '' || $given === '' || ! hash_equals($expected, $given)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        return $next($request);
    }
}
