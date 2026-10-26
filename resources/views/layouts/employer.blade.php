<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="session-user-id" content="{{ auth()->id() }}" data-role="{{ auth()->user()->role }}">
    <title>@yield('title', 'Employer') · Start Work</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/employer.js'])
</head>
<body class="admin-shell employer-shell antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] focus:bg-white focus:p-3">Lewati navigasi</a>
    <div x-data="{ menuOpen: false, desktop: window.matchMedia('(min-width: 1024px)').matches }" @resize.window="desktop = window.matchMedia('(min-width: 1024px)').matches" @keydown.escape.window="if (menuOpen) { menuOpen = false; $nextTick(() => $refs.menuButton.focus()) }" class="min-h-screen lg:flex">
        <div x-cloak x-show="menuOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="menuOpen = false; $nextTick(() => $refs.menuButton.focus())" aria-hidden="true"></div>
        <aside id="employer-menu" :class="menuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" :inert="!menuOpen && !desktop" :aria-hidden="(!menuOpen && !desktop).toString()" class="fixed inset-y-0 left-0 z-50 flex w-full shrink-0 -translate-x-full flex-col overflow-y-auto bg-slate-50 text-slate-700 transition-transform sm:w-[300px] lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:w-[244px] lg:translate-x-0" aria-label="Navigasi Employer">
            <div class="flex h-16 items-center justify-between px-6">
                <a href="{{ route('employer.dashboard') }}" class="text-lg font-bold tracking-tight text-slate-900" aria-label="Start Work Employer, dashboard">startwork<span class="text-[#B85B12]">.</span></a>
                <button x-ref="closeMenu" type="button" class="rounded p-2 lg:hidden" @click="menuOpen = false; $nextTick(() => $refs.menuButton.focus())" aria-label="Tutup menu">Tutup</button>
            </div>
            <p class="px-6 pb-5 text-sm text-slate-500">Ruang kerja Employer</p>
            <nav class="flex-1 space-y-1 px-3" aria-label="Menu utama">
                @foreach ([
                    ['employer.dashboard', 'Dashboard'],
                    ['employer.company', 'Perusahaan'],
                    ['employer.job-posts.*', 'Lowongan kerja'],
                    ['employer.applications.*', 'Pelamar & CV'],
                ] as [$routeName, $label])
                    <a href="{{ route(str_replace('.*', '.index', $routeName)) }}" @class([
                        'block rounded-lg px-4 py-2.5 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#B85B12]',
                        'bg-white font-semibold text-slate-900' => request()->routeIs($routeName),
                        'text-slate-600 hover:bg-white' => ! request()->routeIs($routeName),
                    ]) @if (request()->routeIs($routeName)) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <div class="px-6 py-5">
                <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->full_name }}</p>
                <p class="mt-1 truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
            </div>
        </aside>
        <div class="min-w-0 flex-1" :inert="menuOpen && !desktop">
            <header class="flex min-h-16 items-center justify-between gap-4 bg-slate-50 px-4 sm:px-6 lg:px-8">
                <button x-ref="menuButton" type="button" class="-ml-2 flex h-11 w-11 items-center justify-center lg:hidden" @click="menuOpen = true; $nextTick(() => $refs.closeMenu.focus())" aria-label="Buka menu" aria-controls="employer-menu" :aria-expanded="menuOpen.toString()">Menu</button>
                <span class="truncate text-sm text-slate-600">Start Work / @yield('section_label', 'Employer')</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="admin-button-quiet">Keluar</button></form>
            </header>
            <main id="main-content" class="lg:p-2 lg:pl-0">
                <div class="admin-sheet min-h-[calc(100vh-5rem)] px-4 pb-12 pt-7 sm:px-6 lg:px-8 lg:pt-8">
                    <div id="employer-feedback" aria-live="polite" class="mb-5 hidden"></div>
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html>
