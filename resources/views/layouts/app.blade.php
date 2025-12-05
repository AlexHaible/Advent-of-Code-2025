<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Advent of Code 2025' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body class="bg-slate-900 text-slate-100 h-screen antialiased overflow-hidden">
<div class="h-full flex flex-col">
    <header class="border-b border-slate-800 bg-slate-950/70 backdrop-blur">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">
                    Advent of Code 2025
                </h1>
                <p class="text-xs text-slate-400">
                    Local runner &amp; terminal
                </p>
            </div>
            <livewire:run-command
                :command-key="'tests'"
                :label="'Run Test Suite'"
                :file="null"
            />
            <div class="text-xs text-slate-500">
                {{ now()->toDateTimeString() }}
            </div>
        </div>
    </header>

    <main class="flex-1 overflow-hidden">
        <div class="max-w-5xl mx-auto px-4 py-6">
            @yield('content')
        </div>
    </main>
</div>
@livewireScripts
<script>
    document.addEventListener('livewire:init', () => {
        if (typeof Livewire === 'undefined') {
            return;
        }

        Livewire.on('terminalUpdated', () => {
            const scroller = document.querySelector('[data-terminal-scroller]');
            if (scroller) {
                scroller.scrollTop = scroller.scrollHeight;
            }
        });
    });
</script>
</body>
</html>