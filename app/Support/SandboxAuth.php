<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SandboxAuth
{
    public static function enabled(?Request $request = null): bool
    {
        if (app()->environment('production')) {
            return false;
        }

        $request ??= request();
        $host = $request->getHost();

        foreach (self::allowedHosts() as $pattern) {
            if ($pattern !== '' && Str::is($pattern, $host)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected static function allowedHosts(): array
    {
        $raw = (string) config('services.vatger.sandbox_allowed_hosts', '');

        return array_filter(array_map('trim', explode(',', $raw)));
    }
}
