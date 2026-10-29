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
    <header class="jobseeker-header relative border-b" x-data="{ menuOpen: false }" @keyup.escape.window="menuOpen = false">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="text-xl font-extrabold tracking-tight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600"><span class="jobseeker-wordmark-start">Start</span><span class="jobseeker-wordmark-work">Work</span></a>
            <button type="button" class="rounded-lg border border-slate-600 px-3 py-2 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 md:hidden" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="public-navigation">Menu</button>
            <nav id="public-navigation" aria-label="Navigasi utama" x-show="menuOpen" style="display:none" @click.outside="menuOpen = false" class="absolute left-0 right-0 top-full z-30 flex flex-col items-start gap-5 border-b px-4 py-5 text-sm font-medium shadow-sm md:static md:!flex md:flex-row md:items-center md:border-0 md:bg-transparent md:p-0 md:shadow-none">
                <a href="{{ route('jobs.index') }}" class="focus-visible:underline">Lowongan</a>
                <a href="{{ route('companies.index') }}" class="focus-visible:underline">Perusahaan</a>
                <a href="{{ route('articles.index') }}" class="focus-visible:underline">Blog</a>
                <a href="{{ route('about') }}" class="focus-visible:underline">Tentang</a>
                <a href="{{ route('faqs') }}" class="focus-visible:underline">FAQs</a>
                @auth
                    @if (auth()->user()->role === 'jobseeker')
                        <a href="{{ route('jobseeker.profile') }}" class="focus-visible:underline">Profil & CV</a>
                        <a href="{{ route('jobseeker.applications') }}" class="focus-visible:underline">Lamaran Saya</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="focus-visible:underline">Dashboard</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="font-semibold focus-visible:underline">Keluar</button></form>
                @else
                    <a href="{{ route('login') }}" class="focus-visible:underline">Masuk</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Daftar</a>
                @endauth
            </nav>
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
                                <li><a href="{{ route('jobseeker.profile') }}">Profil & CV</a></li>
                                <li><a href="{{ route('jobseeker.applications') }}">Lamaran Saya</a></li>
                            @endif
                        @else
                            <li><a href="{{ route('register') }}">Daftar pencari kerja</a></li>
                        @endauth
                    </ul>
                </nav>
                <nav aria-label="Footer perusahaan">
                    <h2 class="text-sm font-bold text-white">Perusahaan</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="{{ route('employer.register') }}">Registrasi Perusahaan</a></li>
                        <li><a href="{{ route('employer.login') }}">Untuk Perusahaan</a></li>
                    </ul>
                </nav>
                <nav aria-label="Footer tentang kami">
                    <h2 class="text-sm font-bold text-white">Tentang Kami</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="{{ route('about') }}">Tentang StartWork</a></li>
                        <li><a href="{{ route('faqs') }}">FAQs</a></li>
                        <li><a href="{{ route('articles.index') }}">Artikel & berita</a></li>
                    </ul>
                </nav>
                <div>
                    <h2 class="text-sm font-bold text-white">Kontak</h2>
                    <p class="mt-4 max-w-xs text-sm leading-6">Informasi kontak resmi belum tersedia. Untuk jawaban umum, kunjungi <a href="{{ route('faqs') }}" class="font-semibold underline">FAQs</a>.</p>
                </div>
            </div>
            <p class="mt-12 border-t border-white/10 pt-6 text-sm">© {{ date('Y') }} StartWork</p>
        </div>
    </footer>
</body>
</html>
