<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Gates the VATSIM Connect sandbox login (see routes/auth.php and
 * VatsimOAuthController::sandboxRedirect/sandboxCallback).
 *
 * Two independent checks must both pass:
 *   1. The application must not be running in the `production` environment.
 *   2. The request host must match one of the configured allowed dev hosts.
 *
 * The host check exists as a fallback in case APP_ENV is ever misconfigured on a
 * real deployment — a stray "local"/"testing" APP_ENV alone is not enough to expose
 * sandbox login, since the request would still be arriving on the production host.
 */
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
