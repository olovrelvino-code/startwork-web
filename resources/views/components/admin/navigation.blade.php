<aside :class="menuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed inset-y-0 left-0 z-50 flex w-[240px] shrink-0 -translate-x-full flex-col bg-[#17394B] text-white transition-transform lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:translate-x-0" aria-label="Navigasi Admin Internal">
    <div class="flex h-16 items-center justify-between px-4 sm:px-5">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 text-white" aria-label="Start Work Admin, dashboard">
            <img src="{{ asset('images/asset/STARTWORK.png') }}" alt="StartWork" class="block h-8 w-auto max-w-[155px] object-contain">
        </a>
        <button type="button" class="rounded p-1.5 text-white/80 hover:bg-white/10 hover:text-white lg:hidden" @click="menuOpen = false" aria-label="Tutup menu"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 5l14 14M19 5L5 19" /></svg></button>
    </div>
    <div class="px-4 pb-3 text-[11px] font-medium tracking-wide text-white/60 sm:px-5">Ruang kerja Admin Internal</div>
    <nav class="admin-sidebar-scroll min-h-0 flex-1 space-y-0.5 overflow-y-auto px-2 pb-4" aria-label="Menu utama">
        @foreach ([
            ['admin.dashboard', 'Dashboard', 'layout-dashboard'],
            ['admin.companies.index', 'Perusahaan', 'building-2'],
            ['admin.job-posts.index', 'Lowongan Kerja', 'briefcase-business'],
            ['admin.applicants.index', 'Pelamar & CV', 'users-round'],
            ['admin.articles.index', 'Artikel & Berita', 'newspaper'],
            ['admin.packages.index', 'Paket & Promosi', 'package'],
        ] as [$routeName, $label, $icon])
            <a href="{{ route($routeName) }}" @class([
                'flex min-h-9 items-center gap-2.5 rounded-r-lg border-l-[3px] px-3 py-2 text-[13px] transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#DE7C23]',
                'border-[#DE7C23] bg-[#21546F] font-semibold text-white' => request()->routeIs(str_replace('.index', '.*', $routeName)) || request()->routeIs($routeName),
                'border-transparent font-medium text-white/80 hover:bg-white/10 hover:text-white' => ! (request()->routeIs(str_replace('.index', '.*', $routeName)) || request()->routeIs($routeName)),
            ]) @if (request()->routeIs(str_replace('.index', '.*', $routeName)) || request()->routeIs($routeName)) aria-current="page" @endif><img src="{{ asset('images/admin-icons/'.$icon.'.svg') }}" alt="" class="h-4 w-4 shrink-0 brightness-0 invert"><span class="truncate">{{ $label }}</span></a>
        @endforeach
        <details open class="group pt-1.5">
            <summary class="flex min-h-9 cursor-pointer list-none items-center gap-2.5 rounded-r-lg border-l-[3px] border-transparent px-3 py-2 text-[13px] font-semibold text-white/90 transition-colors hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#DE7C23] [&::-webkit-details-marker]:hidden">
                <img src="{{ asset('images/admin-icons/credit-card.svg') }}" alt="" class="h-4 w-4 shrink-0 brightness-0 invert">
                <span class="min-w-0 flex-1 truncate">Kelola Subscription</span>
                <svg aria-hidden="true" class="h-3.5 w-3.5 shrink-0 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="ml-4 mt-0.5 space-y-0.5 border-l border-white/20 pl-2">
                @foreach ([
                    ['admin.transactions.dashboard', 'Dashboard', 'chart-no-axes-combined'],
                    ['admin.transactions.requests', 'Permintaan', 'clipboard-list'],
                    ['admin.transactions.history', 'Riwayat Transaksi', 'receipt-text'],
                    ['admin.transactions.subscriptions', 'Status Subscription', 'layers'],
                ] as [$routeName, $label, $icon])
                    <a href="{{ route($routeName) }}" @class([
                        'flex min-h-[32px] items-center gap-2 rounded-r-lg border-l-[2px] px-2 py-1 text-xs font-medium leading-tight transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#DE7C23]',
                        'border-[#DE7C23] bg-[#21546F] text-white font-semibold' => request()->routeIs($routeName) || ($routeName === 'admin.transactions.requests' && request()->routeIs('admin.transactions.show')),
                        'border-transparent text-white/75 hover:bg-white/10 hover:text-white' => ! request()->routeIs($routeName) && ! ($routeName === 'admin.transactions.requests' && request()->routeIs('admin.transactions.show')),
                    ]) @if (request()->routeIs($routeName)) aria-current="page" @endif><img src="{{ asset('images/admin-icons/'.$icon.'.svg') }}" alt="" class="h-3.5 w-3.5 shrink-0 brightness-0 invert"><span class="truncate">{{ $label }}</span></a>
                @endforeach
            </div>
        </details>
    </nav>
    <x-account-menu />
</aside>
