<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'No API key provided'], 401);
        }

        $apiKey = ApiKey::findByPlainKey($token);

        if (!$apiKey) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        if (!$apiKey->isValid()) {
            return response()->json(['error' => 'API key is inactive or expired'], 401);
        }

        if ($permission && !$apiKey->hasPermission($permission)) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $apiKey->recordUsage($request->ip());

        return $next($request);
    }
}