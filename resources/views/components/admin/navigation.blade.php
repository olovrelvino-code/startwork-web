<aside :class="menuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed inset-y-0 left-0 z-50 flex w-[244px] shrink-0 -translate-x-full flex-col bg-slate-900 text-slate-300 transition-transform lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:translate-x-0" aria-label="Navigasi Admin Internal">
    <div class="flex h-20 items-center justify-between px-6">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-3 text-white" aria-label="Start Work Admin, dashboard">
            <span class="h-7 w-1 rounded-full bg-[#EE8A34]"></span>
            <span class="text-lg font-bold tracking-tight">startwork<span class="text-[#EE8A34]">.</span></span>
        </a>
        <button type="button" class="rounded p-2 text-white lg:hidden" @click="menuOpen = false" aria-label="Tutup menu"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 5l14 14M19 5L5 19" /></svg></button>
    </div>
    <div class="px-6 pb-7 text-xs font-medium text-slate-400">Ruang kerja Admin Internal</div>
    <nav class="flex-1 space-y-1 px-3" aria-label="Menu utama">
        @foreach ([
            ['admin.dashboard', 'Dashboard'],
            ['admin.companies.index', 'Perusahaan'],
            ['admin.job-posts.index', 'Lowongan Kerja'],
            ['admin.applicants.index', 'Pelamar & CV'],
            ['admin.articles.index', 'Artikel / News'],
            ['admin.packages.index', 'Paket & Promo'],
        ] as [$routeName, $label])
            <a href="{{ route($routeName) }}" @class([
                'block rounded-lg px-4 py-2.5 text-sm transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#EE8A34]',
                'bg-slate-800 font-semibold text-white' => request()->routeIs(str_replace('.index', '.*', $routeName)) || request()->routeIs($routeName),
                'text-slate-300 hover:bg-slate-800/70 hover:text-white' => ! (request()->routeIs(str_replace('.index', '.*', $routeName)) || request()->routeIs($routeName)),
            ]) @if (request()->routeIs(str_replace('.index', '.*', $routeName)) || request()->routeIs($routeName)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <div class="border-t border-slate-800 px-6 py-5">
        <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->full_name ?? auth()->user()->email }}</p>
        <p class="mt-1 truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
    </div>
</aside>
