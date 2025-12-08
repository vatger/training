<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsMentor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            abort(403, 'Authentication required.');
        }

        if ($user->isSuperuser()) {
            return $next($request);
        }

        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        if (!$user->isMentor()) {
            abort(403, 'This action requires mentor permissions.');
        }

        return $next($request);
    }
}