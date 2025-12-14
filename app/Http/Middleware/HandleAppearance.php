<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Models\UserSetting;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $theme = 'system';

        // Check if user is authenticated and has settings
        if ($request->user()) {
            $settings = UserSetting::where('user_id', $request->user()->id)->first();
            if ($settings) {
                $theme = $settings->theme;
            }
        } else {
            // Fall back to cookie for non-authenticated users
            $theme = $request->cookie('appearance') ?? 'system';
        }

        View::share('theme', $theme);

        return $next($request);
    }
}