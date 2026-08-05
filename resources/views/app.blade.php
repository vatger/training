<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($theme ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @if(config('app.env') === 'production')
            <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
        @endif

        <script>
            (function() {
                const theme = '{{ $theme ?? "system" }}';

                if (theme === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                } else if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <style>
            html {
                background-color: oklch(0.958 0.003 264.69);
            }

            html.dark {
                background-color: oklch(0.217 0.011 260.68);
            }
        </style>

        <title inertia>{{ config('app.name') }}</title>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">

        <meta name="theme-color" content="#2B3F55" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#171A1F" media="(prefers-color-scheme: dark)">

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
