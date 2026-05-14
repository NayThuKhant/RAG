<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <script>
            (function () {
                function applyTheme() {
                    var t = localStorage.getItem('theme');
                    if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
                applyTheme();
                document.addEventListener('livewire:navigated', applyTheme);
            })();
        </script>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="h-screen overflow-hidden bg-gray-50 dark:bg-gemini-900">
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('sidebar', {
                    open: window.innerWidth >= 1024,
                });
            });
        </script>

        <div class="flex h-full" x-data>
            @persist('sidebar')
                <div
                    :class="$store.sidebar.open ? 'lg:w-72' : 'lg:w-0'"
                    class="shrink-0 w-0 h-full transition-[width] duration-200 overflow-hidden"
                >
                    @livewire('topics-sidebar')
                </div>
            @endpersist

            <main class="flex-1 min-w-0 flex flex-col overflow-hidden">
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
