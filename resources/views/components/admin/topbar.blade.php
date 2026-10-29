<header class="flex min-h-16 items-center justify-between gap-4 bg-slate-50 px-4 sm:px-6 lg:px-8">
    <div class="flex min-w-0 items-center gap-3">
        <button type="button" class="-ml-2 flex h-11 w-11 items-center justify-center rounded-lg text-slate-700 lg:hidden" @click="menuOpen = true" aria-label="Buka menu" :aria-expanded="menuOpen.toString()">
            <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
        <span class="truncate text-sm font-medium text-slate-600">Start Work <span class="mx-2 text-slate-400">/</span> @yield('section_label', 'Admin Internal')</span>
    </div>
    <div class="flex shrink-0 items-center gap-3">
        <span class="hidden text-xs font-medium text-slate-500 sm:inline">Sesi Admin</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="admin-button-quiet">Keluar</button>
        </form>
    </div>
</header>
