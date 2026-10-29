<aside :class="menuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed inset-y-0 left-0 z-50 flex w-[244px] shrink-0 -translate-x-full flex-col bg-slate-900 text-slate-300 transition-transform lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:translate-x-0" aria-label="Navigasi Admin Internal">
    <div class="flex h-20 items-center justify-between px-6">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-3 text-white" aria-label="Start Work Admin, dashboard">
            <span class="h-7 w-1 rounded-full bg-[#EE8A34]"></span>
            <span class="text-lg font-bold tracking-tight">startwork<span class="text-[#EE8A34]">.</span></span>
        </a>
        <button type="button" class="rounded p-2 text-white lg:hidden" @click="menuOpen = false" aria-label="Tutup menu"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 5l14 14M19 5L5 19" /></svg></button>
    </div>
    <div class="px-6 pb-7 text-xs font-medium text-slate-400">Ruang kerja Admin Internal</div>
    <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3" aria-label="Menu utama">
        @foreach ([
            ['admin.dashboard', 'Dashboard', 'M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z'],
            ['admin.companies.index', 'Perusahaan', 'M4 21V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v16M4 21h16M17 9h1a2 2 0 0 1 2 2v10M8 8h.01M12 8h.01M8 12h.01M12 12h.01'],
            ['admin.job-posts.index', 'Lowongan Kerja', 'M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8ZM8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18'],
            ['admin.applicants.index', 'Pelamar & CV', 'M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M18 8a3 3 0 0 1 0 6M22 20v-2a4 4 0 0 0-3-3.87'],
            ['admin.articles.index', 'Artikel / News', 'M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2M15 2v6h5M8 12h8M8 16h8'],
            ['admin.packages.index', 'Paket & Promo', 'M3 5h18v14H3zM3 10h18M7 15h3'],
        ] as [$routeName, $label, $icon])
            <a href="{{ route($routeName) }}" @class([
                'flex items-center gap-3 rounded-lg border-l-2 px-4 py-2.5 text-sm transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#EE8A34]',
                'border-[#EE8A34] bg-slate-800 font-semibold text-white' => request()->routeIs(str_replace('.index', '.*', $routeName)) || request()->routeIs($routeName),
                'border-transparent text-slate-300 hover:bg-slate-800/70 hover:text-white' => ! (request()->routeIs(str_replace('.index', '.*', $routeName)) || request()->routeIs($routeName)),
            ]) @if (request()->routeIs(str_replace('.index', '.*', $routeName)) || request()->routeIs($routeName)) aria-current="page" @endif><svg aria-hidden="true" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}" /></svg><span>{{ $label }}</span></a>
        @endforeach
    </nav>
    <div class="flex shrink-0 items-center gap-3 border-t border-slate-800 px-5 py-4">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-700 text-sm font-semibold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->full_name ?: auth()->user()->email, 0, 1)) }}</span>
        <div class="min-w-0"><p class="truncate text-sm font-semibold text-white">{{ auth()->user()->full_name ?: auth()->user()->email }}</p><p class="truncate text-xs text-slate-400">Admin Internal</p></div>
    </div>
</aside>
