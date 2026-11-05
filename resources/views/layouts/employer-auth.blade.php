<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Start Work</title>
    <link rel="icon" type="image/png" href="{{ asset('images/asset/Logo Start Work favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/employer.js'])
</head>
<body class="admin-shell min-h-screen bg-white antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:p-3">Lewati navigasi</a>
    @if (request()->routeIs('employer.login', 'employer.register', 'employer.setup'))
    <div class="mx-auto flex min-h-screen max-w-6xl flex-col px-4 sm:px-6">
        <header class="flex items-center justify-between border-b border-slate-200 py-4 sm:py-5">
            <a href="{{ url('/') }}" class="rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" aria-label="StartWork, halaman utama"><img src="{{ asset('images/asset/Logo Start Work B transparan.png') }}" alt="StartWork" class="block h-9 w-auto max-w-[180px] object-contain sm:h-11 sm:max-w-[220px]"></a>
            <a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-slate-700 hover:text-blue-700">Jelajahi Lowongan</a>
        </header>
        <main id="main-content" class="grid flex-1 items-start gap-8 py-7 sm:gap-10 sm:py-10 lg:grid-cols-[minmax(0,1fr)_minmax(360px,460px)] lg:items-stretch lg:gap-12">
            <section class="order-2 mx-auto w-full max-w-xl lg:order-1" aria-label="Ruang kerja Employer">
                <div class="auth-promo-panel">
                    <img src="{{ asset('images/asset/employer.webp') }}" alt="Tim Employer berdiskusi dalam pertemuan kerja">
                    <div class="auth-promo-content">
                        <h1>Temukan kandidat untuk mengembangkan tim Anda.</h1>
                        <p>Publikasikan kebutuhan, kelola lamaran, dan temukan talenta yang sesuai.</p>
                        <ul class="auth-promo-benefits" aria-label="Manfaat untuk Employer">
                            <li>Buat dan kelola lowongan dengan mudah</li>
                            <li>Pantau kandidat dan lamaran di satu tempat</li>
                            <li>Jangkau pencari kerja aktif di StartWork</li>
                        </ul>
                    </div>
                </div>
            </section>
            <div class="order-1 mx-auto w-full max-w-[460px] lg:order-2 lg:mx-0">
                <div role="group" aria-label="Pilih jenis akun" data-role-switch data-role="employer" class="auth-role-switch mb-4 rounded-xl p-1">
                    <span class="auth-role-indicator" aria-hidden="true"></span>
                    @if (request()->routeIs('employer.setup'))
                        <form method="POST" action="{{ route('logout') }}" class="relative z-10 flex-1">@csrf<input type="hidden" name="next" value="jobseeker"><button type="submit" data-auth-role="jobseeker" class="auth-role-button w-full text-slate-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Jobseeker</button></form>
                    @else
                        <a href="{{ request()->routeIs('employer.register') || request()->query('mode') === 'register' ? route('login', ['mode' => 'register']) : route('login') }}" data-auth-role="jobseeker" class="auth-role-button text-center text-slate-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Jobseeker</a>
                    @endif
                    <button type="button" data-auth-role="employer" aria-pressed="true" class="auth-role-button text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Employer</button>
                </div>
                <div data-employer-auth-content>@yield('content')</div>
            </div>
        </main>
    </div>
    @else
    <div class="min-h-screen lg:grid @yield('auth_grid_columns', 'lg:grid-cols-[minmax(320px,0.88fr)_minmax(0,1.12fr)]')">
        <aside class="auth-visual auth-visual--employer @if (request()->routeIs('employer.register')) auth-visual--employer-register min-h-56 flex-col items-stretch @else min-h-44 flex-row items-center @endif flex justify-between gap-4 px-5 py-5 sm:min-h-52 sm:px-10 sm:py-8 lg:min-h-screen lg:flex-col lg:items-stretch lg:px-10 lg:py-10 xl:px-16" aria-label="Alur ruang kerja Employer">
            <div class="flex w-full items-center justify-between gap-3">
                <a href="{{ url('/') }}" class="w-fit shrink-0 rounded-md bg-white px-2 py-1 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4" aria-label="StartWork, halaman utama"><img src="{{ asset('images/asset/Logo Start Work B transparan.png') }}" alt="StartWork" class="block h-8 w-auto max-w-[155px] object-contain sm:h-10 sm:max-w-[200px]"></a>
                @if (request()->routeIs('employer.register'))
                    <a href="{{ route('jobs.index') }}" class="text-xs font-semibold text-white hover:text-orange-200 sm:text-sm">Jelajahi Lowongan</a>
                @endif
            </div>
            <div class="max-w-md @if (request()->routeIs('employer.register')) mt-auto @endif">
                @unless (request()->routeIs('employer.register'))
                    <img src="{{ asset('images/jobseeker/status-3d.svg') }}" alt="" class="mb-3 w-14 drop-shadow-lg sm:mb-4 sm:w-20 lg:mb-8 lg:w-40">
                @endunless
                <h2 class="{{ request()->routeIs('employer.register') ? 'block text-2xl' : 'hidden text-4xl' }} font-semibold leading-tight tracking-tight text-white lg:block lg:text-4xl">Siapkan ruang kerja perusahaan Anda.</h2>
                <p class="hidden text-base leading-relaxed text-white/90 lg:mt-6 lg:block">Buat akun, lengkapi profil perusahaan, lalu kelola lowongan dan lamaran dari satu tempat.</p>
                @if (($registrationStep ?? null) && ! request()->routeIs('employer.register'))
                    <x-employer.registration-progress :step="$registrationStep" variant="dark" class="mt-10" />
                @endif
            </div>
            <p class="hidden max-w-sm text-sm leading-relaxed text-white/90 lg:block">Akses perusahaan dan pelamar dibatasi untuk akun Employer pemiliknya.</p>
        </aside>
        <main id="main-content" class="flex min-h-[calc(100vh-11rem)] items-start bg-[#F3F1ED] px-5 py-8 sm:min-h-[calc(100vh-13rem)] sm:px-10 lg:min-h-screen lg:items-center lg:px-16">
            <div class="mx-auto w-full @yield('content_width', 'max-w-[460px]')">
                <div class="mb-6 lg:hidden"><p class="text-sm font-semibold text-[#95420D]">Ruang kerja Employer</p></div>
                <div data-employer-auth-content>@yield('content')</div>
            </div>
        </main>
    </div>
    @endif
</body>
</html>
