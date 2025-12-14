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
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name') }}</title>

        <link rel="icon" href="https://vatsim-germany.org/favicon.svg" type="image/png">
        <link rel="icon" href="https://vatsim-germany.org/favicon-96x96.png" type="image/svg+xml">
        <link rel="shortcut icon" href="https://vatsim-germany.org/favicon.ico">
        <link rel="apple-touch-icon" href="https://vatsim-germany.org/apple-touch-icon.png" sizes="180x180">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>