<?php

namespace App\Http\Middleware;

use App\Support\SandboxAuth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSandboxAuthEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(SandboxAuth::enabled($request), 404);

        return $next($request);
    }
}
