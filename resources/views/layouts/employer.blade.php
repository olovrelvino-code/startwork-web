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
        <aside id="employer-menu" :class="menuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" :inert="!menuOpen && !desktop" :aria-hidden="(!menuOpen && !desktop).toString()" class="fixed inset-y-0 left-0 z-50 flex w-full shrink-0 -translate-x-full flex-col bg-slate-50 text-slate-700 transition-transform sm:w-[300px] lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:w-[244px] lg:translate-x-0" aria-label="Navigasi Employer">
            <div class="flex h-16 items-center justify-between px-6">
                <a href="{{ route('employer.dashboard') }}" class="text-lg font-bold tracking-tight text-slate-900" aria-label="Start Work Employer, dashboard">startwork<span class="text-[#B85B12]">.</span></a>
                <button x-ref="closeMenu" type="button" class="rounded p-2 lg:hidden" @click="menuOpen = false; $nextTick(() => $refs.menuButton.focus())" aria-label="Tutup menu">Tutup</button>
            </div>
            <p class="px-6 pb-5 text-sm text-slate-500">Ruang kerja Employer</p>
            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3" aria-label="Menu utama">
                @foreach ([
                    ['employer.dashboard', 'Dashboard', 'M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z'],
                    ['employer.company', 'Perusahaan', 'M4 21V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v16M4 21h16M17 9h1a2 2 0 0 1 2 2v10M8 8h.01M12 8h.01M8 12h.01M12 12h.01'],
                    ['employer.job-posts.*', 'Lowongan kerja', 'M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8ZM8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18'],
                    ['employer.applications.*', 'Pelamar & CV', 'M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M18 8a3 3 0 0 1 0 6M22 20v-2a4 4 0 0 0-3-3.87'],
                ] as [$routeName, $label, $icon])
                    <a href="{{ route(str_replace('.*', '.index', $routeName)) }}" @class([
                        'flex items-center gap-3 rounded-lg border-l-2 px-4 py-2.5 text-sm transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#B85B12]',
                        'border-[#B85B12] bg-white font-semibold text-slate-900' => request()->routeIs($routeName),
                        'border-transparent text-slate-600 hover:bg-white' => ! request()->routeIs($routeName),
                    ]) @if (request()->routeIs($routeName)) aria-current="page" @endif><svg aria-hidden="true" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}" /></svg><span>{{ $label }}</span></a>
                @endforeach
            </nav>
            <div class="flex shrink-0 items-center gap-3 border-t border-slate-200 px-5 py-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#EBDCCF] text-sm font-semibold text-[#723B12]" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->full_name ?: auth()->user()->email, 0, 1)) }}</span>
                <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->full_name ?: auth()->user()->email }}</p><p class="truncate text-xs text-slate-500">Employer</p></div>
            </div>
        </aside>
        <div class="min-w-0 flex-1" :inert="menuOpen && !desktop">
            <header class="flex min-h-16 flex-wrap items-center gap-3 bg-slate-50 px-4 py-2 sm:px-6 lg:px-8">
                <button x-ref="menuButton" type="button" class="-ml-2 flex h-11 w-11 items-center justify-center lg:hidden" @click="menuOpen = true; $nextTick(() => $refs.closeMenu.focus())" aria-label="Buka menu" aria-controls="employer-menu" :aria-expanded="menuOpen.toString()">Menu</button>
                <span class="hidden min-w-0 flex-1 truncate text-sm text-slate-600 sm:block">Start Work / @yield('section_label', 'Employer')</span>
                <div class="ml-auto flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <span class="text-xs font-medium text-slate-500">Sesi Employer</span>
                    <button type="button" class="admin-button-secondary" data-upgrade-open aria-haspopup="dialog" aria-controls="employer-upgrade-dialog">Upgrade Plan</button>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="admin-button-quiet">Keluar</button></form>
                </div>
            </header>
            <main id="main-content" class="lg:p-2 lg:pl-0">
                <div class="admin-sheet min-h-[calc(100vh-5rem)] px-4 pb-12 pt-7 sm:px-6 lg:px-8 lg:pt-8">
                    <div id="employer-feedback" aria-live="polite" class="mb-5 hidden"></div>
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    <dialog id="employer-upgrade-dialog" data-upgrade-dialog data-packages-url="{{ url('/api/packages') }}" aria-modal="true" aria-labelledby="upgrade-title" aria-describedby="upgrade-description" class="max-h-[calc(100dvh-2rem)] w-full max-w-5xl overflow-y-auto rounded-2xl border border-slate-400 bg-slate-900 p-0 text-white shadow-xl backdrop:bg-slate-950/65" style="width:min(94vw,62rem)">
        <div class="p-5 sm:p-8 lg:p-10">
            <div class="flex justify-end"><button type="button" data-upgrade-close aria-label="Tutup pilihan paket" class="rounded-lg border border-slate-600 px-3 py-1.5 text-sm text-slate-200 transition-colors hover:border-slate-400 hover:bg-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#E6A66F]">Tutup</button></div>
            <div class="mx-auto max-w-xl text-center"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#E6A66F]">Paket StartWork</p><h2 id="upgrade-title" class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Upgrade Plan</h2><p id="upgrade-description" class="mt-3 text-sm leading-relaxed text-slate-300">Pilih paket sesuai kebutuhan rekrutmen. Pembelian belum tersedia dan pilihan tidak mengubah akses akun.</p></div>
            <p data-upgrade-status class="mt-6 min-h-5 text-center text-sm text-slate-300" role="status" aria-live="polite">Memuat paket…</p>
            <div data-upgrade-packages class="mt-6 grid gap-4 sm:grid-cols-3"></div>
            <template data-upgrade-template>
                <article class="flex min-w-0 flex-col rounded-2xl border border-slate-400 bg-slate-800 p-5 transition-colors data-[featured=true]:border-[#C87431] data-[selected=true]:ring-2 data-[selected=true]:ring-[#E6A66F] sm:p-6">
                    <h3 data-package-name class="text-lg font-semibold text-white"></h3>
                    <p data-package-price class="admin-number mt-6 break-words text-2xl font-semibold tracking-tight text-white lg:text-[1.75rem]"></p>
                    <dl class="mt-6 space-y-3 border-t border-slate-600 pt-5 text-sm"><div class="flex justify-between gap-3"><dt class="text-slate-300">Kuota</dt><dd data-package-quota class="text-right font-semibold text-white"></dd></div><div class="flex justify-between gap-3"><dt class="text-slate-300">Durasi</dt><dd data-package-duration class="text-right font-semibold text-white"></dd></div></dl>
                    <button type="button" data-select-package class="mt-7 w-full rounded-lg border border-[#C87431] bg-[#B85B12] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#9D4D10] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#F5C093] data-[selected=true]:border-[#F5C093] data-[selected=true]:bg-[#613B22]" aria-pressed="false">Pilih paket</button>
                </article>
            </template>
        </div>
    </dialog>
</body>
</html>
