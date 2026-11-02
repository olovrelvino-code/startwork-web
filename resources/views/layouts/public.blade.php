<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth<meta name="session-user-id" content="{{ auth()->id() }}" data-role="{{ auth()->user()->role }}">@endauth
    <meta name="description" content="{{ html_entity_decode(trim($__env->yieldContent('description', 'Temukan peluang kerja dan informasi karier di StartWork.')), ENT_QUOTES | ENT_HTML5, 'UTF-8') }}">
    <title>{{ html_entity_decode(trim($__env->yieldContent('title', 'StartWork')), ENT_QUOTES | ENT_HTML5, 'UTF-8') }} · StartWork</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/jobseeker.js'])
</head>
<body class="jobseeker-shell jobseeker-page min-h-screen bg-slate-50 text-slate-900 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-3 focus:z-50 focus:bg-white focus:p-3">Lewati navigasi</a>
    <header class="jobseeker-header relative border-b" x-data="{ menuOpen: false, profileOpen: false }" @keyup.escape.window="menuOpen = false; profileOpen = false">
        <div class="mx-auto grid max-w-7xl grid-cols-[auto_1fr] items-center gap-3 px-4 py-3 sm:px-6 md:grid-cols-[1fr_auto_1fr] lg:px-8">
            <a href="{{ url('/') }}" class="text-lg font-extrabold tracking-tight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600 sm:text-xl"><span class="jobseeker-wordmark-start">Start</span><span class="jobseeker-wordmark-work">Work</span></a>
            <nav id="public-navigation" aria-label="Navigasi utama" x-show="menuOpen" style="display:none" @click.outside="menuOpen = false" class="absolute left-0 right-0 top-full z-30 flex flex-col items-start gap-5 border-b px-4 py-5 text-sm font-medium shadow-sm md:static md:col-start-2 md:row-start-1 md:!flex md:flex-row md:items-center md:justify-center md:border-0 md:bg-transparent md:p-0 md:shadow-none">
                <a href="{{ route('jobs.index') }}" class="focus-visible:underline" @if(request()->routeIs('jobs.*')) aria-current="page" @endif>Lowongan</a>
                <a href="{{ route('companies.index') }}" class="focus-visible:underline" @if(request()->routeIs('companies.*')) aria-current="page" @endif>Perusahaan</a>
                <a href="{{ route('articles.index') }}" class="focus-visible:underline" @if(request()->routeIs('articles.*')) aria-current="page" @endif>Blog</a>
            </nav>
            <div class="col-start-2 flex min-w-0 items-center justify-end gap-2 text-sm md:col-start-3 md:row-start-1">
                @auth
                    @if (auth()->user()->role === 'jobseeker')
                        @php
                            $navProfile = \Illuminate\Support\Facades\DB::table('jobseeker_profiles')->where('user_id', auth()->id())->first(['full_name', 'profile_photo']);
                            $navName = $navProfile?->full_name ?: auth()->user()->name ?: 'Pencari Kerja';
                        @endphp
                        <div class="relative min-w-0" @click.outside="profileOpen = false">
                            <button x-ref="profileButton" type="button" class="flex min-w-0 items-center gap-2 rounded-lg px-1 py-1 font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" @click="profileOpen = !profileOpen; menuOpen = false" :aria-expanded="profileOpen.toString()" aria-controls="jobseeker-account-menu">
                                <span class="relative grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-full bg-white/15 text-sm" aria-hidden="true"><span data-nav-photo-fallback>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($navName, 0, 1)) }}</span><img data-nav-photo @if($navProfile?->profile_photo) src="{{ url('/api/jobseeker/profile/photo') }}" @endif alt="" class="absolute inset-0 hidden h-full w-full object-cover"></span>
                                <span data-nav-name class="max-w-[4rem] truncate text-left sm:max-w-36">{{ $navName }}</span><span aria-hidden="true" class="text-xs">▾</span>
                            </button>
                            <div id="jobseeker-account-menu" x-show="profileOpen" style="display:none" @keydown.escape.stop="profileOpen = false; $refs.profileButton.focus()" class="absolute right-0 top-full z-50 mt-2 w-52 rounded-lg border border-white/15 bg-[var(--jobseeker-primary-dark)] p-1.5 text-white shadow-lg">
                                <a href="{{ route('jobseeker.profile') }}" class="block rounded-md px-3 py-2.5 hover:bg-white/10 focus-visible:outline focus-visible:outline-2" @if(request()->routeIs('jobseeker.profile')) aria-current="page" @endif>Profil Saya</a>
                                <a href="{{ route('jobseeker.applications') }}" class="block rounded-md px-3 py-2.5 hover:bg-white/10 focus-visible:outline focus-visible:outline-2" @if(request()->routeIs('jobseeker.applications')) aria-current="page" @endif>Lamaran Saya</a>
                                <a href="{{ route('jobseeker.saved-jobs') }}" class="block rounded-md px-3 py-2.5 hover:bg-white/10 focus-visible:outline focus-visible:outline-2" @if(request()->routeIs('jobseeker.saved-jobs')) aria-current="page" @endif>Simpan Lamaran</a>
                                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="w-full rounded-md px-3 py-2.5 text-left font-medium hover:bg-white/10 focus-visible:outline focus-visible:outline-2">Log Out</button></form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('dashboard') }}" class="focus-visible:underline">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="font-semibold focus-visible:underline">Keluar</button></form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="font-semibold focus-visible:underline">Masuk</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-blue-600 px-3 py-2 font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Daftar</a>
                @endauth
                <button type="button" aria-label="Menu navigasi" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-slate-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 md:hidden" @click="menuOpen = !menuOpen; profileOpen = false" :aria-expanded="menuOpen.toString()" aria-controls="public-navigation"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16" /></svg></button>
            </div>
        </div>
    </header>
    <main id="main-content">@yield('content')</main>
    <footer class="jobseeker-footer border-t">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                <nav aria-label="Footer pencari kerja">
                    <h2 class="text-sm font-bold text-white">Pencari Kerja</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="{{ route('jobs.index') }}">Lowongan kerja</a></li>
                        <li><a href="{{ route('companies.index') }}">Perusahaan</a></li>
                        @auth
                            @if (auth()->user()->role === 'jobseeker')
                                <li><a href="{{ route('jobseeker.profile') }}">Profil Saya</a></li>
                                <li><a href="{{ route('jobseeker.applications') }}">Lamaran Saya</a></li>
                                <li><a href="{{ route('jobseeker.saved-jobs') }}">Lowongan Tersimpan</a></li>
                            @endif
                        @else
                            <li><a href="{{ route('register') }}">Daftar pencari kerja</a></li>
                        @endauth
                    </ul>
                </nav>
                <nav aria-label="Footer perusahaan">
                    <h2 class="text-sm font-bold text-white">Perusahaan</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        @if (auth()->check() && auth()->user()->role === 'jobseeker')
                            <li><a href="{{ route('account.switch', ['role' => 'employer']) }}">Registrasi Perusahaan</a></li>
                            <li><a href="{{ route('account.switch', ['role' => 'employer']) }}">Untuk Perusahaan</a></li>
                        @else
                            <li><a href="{{ route('employer.register') }}">Registrasi Perusahaan</a></li>
                            <li><a href="{{ route('employer.login') }}">Untuk Perusahaan</a></li>
                        @endif
                    </ul>
                </nav>
                <nav aria-label="Footer tentang StartWork">
                    <h2 class="text-sm font-bold text-white">Tentang StartWork</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="{{ route('about') }}">Tentang StartWork</a></li>
                        <li><a href="{{ route('faqs') }}">FAQs</a></li>
                        <li><a href="{{ route('articles.index') }}">Blog</a></li>
                    </ul>
                </nav>
                <div>
                    <h2 class="text-sm font-bold text-white">Kontak StartWork</h2>
                    <p class="mt-4 max-w-xs text-sm leading-6">Informasi kontak resmi belum tersedia. Untuk jawaban umum, kunjungi <a href="{{ route('faqs') }}" class="font-semibold underline">FAQs</a>.</p>
                    <div class="mt-5 flex items-center gap-3" aria-label="Media sosial StartWork">
                        <span role="img" aria-label="Instagram" title="Instagram" class="jobseeker-social-icon grid h-10 w-10 place-items-center rounded-full border border-white/20"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="18" cy="6" r="1" fill="currentColor" stroke="none"/></svg></span>
                        <span role="img" aria-label="Facebook" title="Facebook" class="jobseeker-social-icon grid h-10 w-10 place-items-center rounded-full border border-white/20"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M14.2 22v-8.6H17l.5-3.5h-3.3V7.7c0-1 .3-1.7 1.8-1.7h1.7V2.9c-.3 0-1.5-.2-2.8-.2-2.8 0-4.7 1.7-4.7 4.8v2.4H7.4v3.5h2.8V22h4Z"/></svg></span>
                        <span role="img" aria-label="Threads" title="Threads" class="jobseeker-social-icon grid h-10 w-10 place-items-center rounded-full border border-white/20"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19.5 8.5C18.4 4.8 15.7 3 12 3 6.8 3 3.5 6.8 3.5 12s3.2 9 8.5 9c4.8 0 8-2.8 8-6.2 0-3.1-2.4-5.2-6.3-5.2-2.7 0-4.3 1.1-4.3 3.1 0 1.7 1.2 2.7 3.1 2.7 2.1 0 3.6-1.4 3.6-3.6 0-3.3-1.7-5.3-5.6-5.8"/></svg></span>
                    </div>
                </div>
            </div>
            <p class="mt-12 border-t border-white/10 pt-6 text-sm">© {{ date('Y') }} StartWork</p>
        </div>
    </footer>
    <dialog id="jobseeker-share-dialog" aria-labelledby="jobseeker-share-title" class="w-[min(26rem,calc(100%-2rem))] rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-xl backdrop:bg-slate-950/60">
        <div class="flex items-start justify-between gap-4"><h2 id="jobseeker-share-title" class="text-xl font-bold">Bagikan lowongan</h2><button type="button" data-share-close aria-label="Tutup" class="rounded-lg px-2 py-1 text-lg focus-visible:outline focus-visible:outline-2">×</button></div>
        <p class="mt-2 text-sm text-slate-600">Bagikan tautan detail lowongan.</p>
        <div class="mt-6 flex flex-wrap gap-3"><a data-share-whatsapp target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-200 px-4 py-2 font-semibold hover:bg-slate-100">WhatsApp</a><button type="button" data-share-copy class="rounded-lg border border-slate-200 px-4 py-2 font-semibold hover:bg-slate-100">Salin link</button><button type="button" data-share-native class="hidden rounded-lg border border-slate-200 px-4 py-2 font-semibold hover:bg-slate-100">Bagikan</button></div>
        <p data-share-feedback role="status" class="mt-3 text-sm text-slate-600" aria-live="polite"></p>
    </dialog>
    <p data-save-status role="status" aria-live="polite" class="pointer-events-none fixed bottom-4 right-4 z-50 hidden max-w-sm rounded-lg bg-[var(--jobseeker-primary-dark)] px-4 py-3 text-sm text-white shadow-lg"></p>
</body>
</html>
