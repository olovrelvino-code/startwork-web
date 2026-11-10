<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth<meta name="session-user-id" content="{{ auth()->id() }}" data-role="{{ auth()->user()->role }}">@endauth
    <meta name="description" content="{{ html_entity_decode(trim($__env->yieldContent('description', 'Temukan peluang kerja dan informasi karier di StartWork.')), ENT_QUOTES | ENT_HTML5, 'UTF-8') }}">
    <title>{{ html_entity_decode(trim($__env->yieldContent('title', 'StartWork')), ENT_QUOTES | ENT_HTML5, 'UTF-8') }} · StartWork</title>
    <link rel="icon" type="image/png" href="{{ asset('images/asset/Logo Start Work favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/jobseeker.js'])
</head>
<body class="jobseeker-shell jobseeker-page min-h-screen bg-slate-50 text-slate-900 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-3 focus:z-50 focus:bg-white focus:p-3">Lewati navigasi</a>
    <header class="jobseeker-header relative border-b" x-data="{ menuOpen: false, profileOpen: false }" @keyup.escape.window="menuOpen = false; profileOpen = false">
        <div class="mx-auto grid max-w-7xl grid-cols-[auto_1fr] items-center gap-3 px-4 py-3 sm:px-6 md:grid-cols-[1fr_auto_1fr] lg:px-8">
            <a href="{{ url('/') }}" class="text-lg font-extrabold tracking-tight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600 sm:text-xl"><img src="{{ asset('images/asset/STARTWORK.png') }}" alt="StartWork Logo" class="block h-7 w-auto max-w-[150px] object-contain sm:h-8"></a>
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
                <div>
                    <a href="{{ url('/') }}" aria-label="StartWork, halaman utama" class="inline-block rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4"><img src="{{ asset('images/asset/Logo Start Work D putih.png') }}" alt="StartWork" width="3406" height="924" loading="lazy" decoding="async" class="block h-auto w-48 max-w-full object-contain sm:w-52"></a>
                    <p class="mt-4 max-w-xs text-sm leading-6 text-white/80">Menghubungkan talenta dengan peluang kerja.</p>
                </div>
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
                    <div class="mt-4 grid gap-2 text-sm">
                        <a href="https://wa.me/628977772099" target="_blank" rel="noopener noreferrer" title="WhatsApp StartWork" aria-label="Hubungi StartWork lewat WhatsApp di +62 897-7772-099" class="flex min-h-10 items-center gap-3 rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"><svg aria-hidden="true" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2a9.8 9.8 0 0 0-8.45 14.77L2.3 22l5.39-1.25A9.8 9.8 0 1 0 12.04 2Zm0 17.8a8 8 0 0 1-4.08-1.12l-.29-.17-3.2.74.76-3.12-.19-.32A8 8 0 1 1 12.04 19.8Zm4.4-5.99c-.24-.12-1.42-.7-1.64-.78-.22-.08-.38-.12-.54.12-.16.24-.62.78-.76.94-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.92-1.18-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.02-.37.1-.49.11-.1.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.78-.19-.46-.39-.4-.54-.41h-.46c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2s.86 2.32.98 2.48c.12.16 1.69 2.58 4.1 3.62.57.25 1.02.4 1.37.51.57.18 1.09.16 1.5.1.46-.07 1.42-.58 1.62-1.14.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28Z"/></svg><span>+62 897-7772-099</span></a>
                        <a href="mailto:startworks.id@gmail.com" title="Email StartWork" aria-label="Kirim email ke startworks.id@gmail.com" class="flex min-h-10 items-center gap-3 rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"><svg aria-hidden="true" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg><span class="break-all">startworks.id@gmail.com</span></a>
                        <a href="https://t.me/startwork_id" target="_blank" rel="noopener noreferrer" title="Telegram StartWork" aria-label="Hubungi StartWork di Telegram, @startwork_id" class="flex min-h-10 items-center gap-3 rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"><svg aria-hidden="true" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="m21.8 3.2-3.2 17.2c-.24 1.22-.88 1.52-1.78.95l-4.92-3.63-2.38 2.3c-.26.26-.48.48-.98.48l.35-5.01L18 7.62c.38-.34-.08-.53-.59-.2L7.07 13.94l-4.46-1.4c-.97-.3-.99-.97.2-1.44L20.24 3.2c.8-.3 1.5.19 1.56 0Z"/></svg><span>@startwork_id</span></a>
                    </div>
                    <h2 class="mt-6 text-sm font-bold text-white">Follow Us</h2>
                    <div class="mt-3 grid w-fit grid-cols-5 gap-2" aria-label="Media sosial StartWork">
                        <a href="https://www.instagram.com/startwork.id/" target="_blank" rel="noopener noreferrer" aria-label="Instagram StartWork" title="Instagram" class="jobseeker-social-icon grid h-10 w-10 place-items-center rounded-full border border-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="18" cy="6" r="1" fill="currentColor" stroke="none"/></svg></a>
                        <a href="https://www.threads.com/@startwork.id" target="_blank" rel="noopener noreferrer" aria-label="Threads StartWork" title="Threads" class="jobseeker-social-icon grid h-10 w-10 place-items-center rounded-full border border-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19.5 8.5C18.4 4.8 15.7 3 12 3 6.8 3 3.5 6.8 3.5 12s3.2 9 8.5 9c4.8 0 8-2.8 8-6.2 0-3.1-2.4-5.2-6.3-5.2-2.7 0-4.3 1.1-4.3 3.1 0 1.7 1.2 2.7 3.1 2.7 2.1 0 3.6-1.4 3.6-3.6 0-3.3-1.7-5.3-5.6-5.8"/></svg></a>
                        <a href="https://www.tiktok.com/@startwork.id" target="_blank" rel="noopener noreferrer" aria-label="TikTok StartWork" title="TikTok" class="jobseeker-social-icon grid h-10 w-10 place-items-center rounded-full border border-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M19.6 7.1a6.8 6.8 0 0 1-4.2-1.5v8.1a5.7 5.7 0 1 1-5-5.7v3.1a2.7 2.7 0 1 0 2 2.6V2.5h3a4.5 4.5 0 0 0 4.2 4.1v.5Z"/></svg></a>
                        <a href="https://x.com/startworkid" target="_blank" rel="noopener noreferrer" aria-label="X StartWork" title="X" class="jobseeker-social-icon grid h-10 w-10 place-items-center rounded-full border border-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3L12 14.9 5.8 22H2.6l7.3-8.4L1.1 2h6.5l4.5 6.7L18.9 2Zm-1.1 18h1.7L6.6 3.9H4.8L17.8 20Z"/></svg></a>
                        <a href="https://www.facebook.com/startwork.id" target="_blank" rel="noopener noreferrer" aria-label="Facebook StartWork" title="Facebook" class="jobseeker-social-icon grid h-10 w-10 place-items-center rounded-full border border-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M14.2 22v-8.6H17l.5-3.5h-3.3V7.7c0-1 .3-1.7 1.8-1.7h1.7V2.9c-.3 0-1.5-.2-2.8-.2-2.8 0-4.7 1.7-4.7 4.8v2.4H7.4v3.5h2.8V22h4Z"/></svg></a>
                    </div>
                </div>
            </div>
            <p class="mt-12 border-t border-white/10 pt-6 text-sm">© {{ date('Y') }} StartWork</p>
        </div>
    </footer>
    <dialog id="jobseeker-share-dialog" aria-labelledby="jobseeker-share-title" class="w-[min(26rem,calc(100%-2rem))] rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-xl backdrop:bg-slate-950/60">
        <div class="flex items-start justify-between gap-4"><h2 id="jobseeker-share-title" class="text-xl font-bold">Bagikan lowongan</h2><button type="button" data-share-close aria-label="Tutup" class="rounded-lg px-2 py-1 text-lg focus-visible:outline focus-visible:outline-2">×</button></div>
        <p class="mt-2 text-sm text-slate-600">Bagikan tautan detail lowongan.</p>
        <div class="mt-6 grid grid-cols-2 gap-3 text-sm font-semibold">
            <a data-share-whatsapp target="_blank" rel="noopener noreferrer" class="flex min-h-11 items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 hover:border-blue-300 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600"><svg aria-hidden="true" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0 12 12 0 0 0 1.8 18.2L0 24l6-1.7A12 12 0 1 0 20.5 3.5ZM12 21.8a9.8 9.8 0 0 1-5-1.4l-.4-.2-3.5 1 1-3.4-.3-.4a9.8 9.8 0 1 1 8.2 4.4Zm5.4-7.3c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1a8 8 0 0 1-4-3.5c-.3-.5.3-.5.8-1.5.1-.2 0-.4 0-.6L8.7 7c-.2-.4-.4-.4-.7-.4h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.7 1.2 2.9a11 11 0 0 0 4.2 3.8c2.1.9 2.9 1 3.9.8 1.1-.2 1.8-.8 2.1-1.5.3-.6.3-1.2.2-1.3Z"/></svg>WhatsApp</a>
            <a data-share-linkedin target="_blank" rel="noopener noreferrer" class="flex min-h-11 items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 hover:border-blue-300 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600"><svg aria-hidden="true" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M20.4 2H3.6C2.7 2 2 2.7 2 3.6v16.8c0 .9.7 1.6 1.6 1.6h16.8c.9 0 1.6-.7 1.6-1.6V3.6c0-.9-.7-1.6-1.6-1.6ZM8 18.7H5V9h3v9.7ZM6.5 7.7a1.7 1.7 0 1 1 0-3.4 1.7 1.7 0 0 1 0 3.4Zm12.2 11h-3v-4.7c0-1.1 0-2.5-1.5-2.5s-1.8 1.2-1.8 2.4v4.8h-3V9h2.9v1.3h.1a3.2 3.2 0 0 1 2.8-1.5c3 0 3.5 2 3.5 4.5v5.4Z"/></svg>LinkedIn</a>
            <a data-share-facebook target="_blank" rel="noopener noreferrer" class="flex min-h-11 items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 hover:border-blue-300 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600"><svg aria-hidden="true" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M14.2 22v-8.6H17l.5-3.5h-3.3V7.7c0-1 .3-1.7 1.8-1.7h1.7V2.9c-.3 0-1.5-.2-2.8-.2-2.8 0-4.7 1.7-4.7 4.8v2.4H7.4v3.5h2.8V22h4Z"/></svg>Facebook</a>
            <a data-share-telegram target="_blank" rel="noopener noreferrer" class="flex min-h-11 items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 hover:border-blue-300 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600"><svg aria-hidden="true" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M21.4 3.1 2.8 10.3c-1.3.5-1.3 1.2-.2 1.5l4.8 1.5 11.1-7c.5-.3 1-.1.6.2l-9 8.1-.3 4.6c.5 0 .7-.2 1-.5l2.3-2.2 4.8 3.5c.9.5 1.5.2 1.7-.8L22.7 4c.3-1.2-.5-1.7-1.3-.9Z"/></svg>Telegram</a>
            <button type="button" data-share-copy class="col-span-2 flex min-h-11 items-center justify-center gap-3 rounded-lg border border-slate-200 px-3 py-2 hover:border-blue-300 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600"><svg aria-hidden="true" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>Copy Link</button>
        </div>
        <p data-share-feedback role="status" class="mt-3 text-sm text-slate-600" aria-live="polite"></p>
    </dialog>
    <p data-save-status role="status" aria-live="polite" class="pointer-events-none fixed bottom-4 right-4 z-50 hidden max-w-sm rounded-lg bg-[var(--jobseeker-primary-dark)] px-4 py-3 text-sm text-white shadow-lg"></p>
</body>
</html>
