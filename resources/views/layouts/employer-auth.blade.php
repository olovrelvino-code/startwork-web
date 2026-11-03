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
    @if (request()->routeIs('employer.login'))
    <div class="mx-auto flex min-h-screen max-w-6xl flex-col px-4 sm:px-6">
        <header class="flex items-center justify-between border-b border-slate-200 py-4 sm:py-5">
            <a href="{{ url('/') }}" class="rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" aria-label="StartWork, halaman utama"><img src="{{ asset('images/asset/Logo Start Work B transparan.png') }}" alt="StartWork" class="block h-9 w-auto max-w-[180px] object-contain sm:h-11 sm:max-w-[220px]"></a>
            <a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-slate-700 hover:text-blue-700">Jelajahi Lowongan</a>
        </header>
        <main id="main-content" class="grid flex-1 items-start gap-8 py-7 sm:gap-10 sm:py-10 lg:grid-cols-[minmax(0,1fr)_minmax(360px,460px)] lg:items-center lg:gap-12">
            <section class="hidden max-w-xl lg:block" aria-label="Ruang kerja Employer">
                <img src="{{ asset('images/jobseeker/status-3d.svg') }}" alt="" class="mb-8 w-52 drop-shadow-lg">
                <p class="text-sm font-semibold text-[#B85B12]">Ruang Employer</p>
                <h1 class="mt-4 text-4xl font-bold leading-tight tracking-tight text-slate-900">Siapkan ruang kerja perusahaan Anda.</h1>
                <p class="mt-5 text-base leading-7 text-slate-600">Buat akun, lengkapi profil perusahaan, lalu kelola lowongan dan lamaran dari satu tempat.</p>
            </section>
            <div class="mx-auto w-full max-w-[460px] lg:mx-0" data-employer-auth-content>@yield('content')</div>
        </main>
    </div>
    @else
    <div class="min-h-screen lg:grid @yield('auth_grid_columns', 'lg:grid-cols-[minmax(320px,0.88fr)_minmax(0,1.12fr)]')">
        <aside class="auth-visual auth-visual--employer @if (request()->routeIs('employer.login')) auth-visual--login @endif flex min-h-44 flex-row items-center justify-between gap-4 px-5 py-5 sm:min-h-52 sm:px-10 sm:py-8 lg:min-h-screen lg:flex-col lg:items-stretch lg:px-10 lg:py-10 xl:px-16" aria-label="Alur ruang kerja Employer">
            <a href="{{ url('/') }}" class="w-fit shrink-0 rounded-md bg-white px-2 py-1 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4" aria-label="StartWork, halaman utama"><img src="{{ asset('images/asset/Logo Start Work B transparan.png') }}" alt="StartWork" class="block h-8 w-auto max-w-[155px] object-contain sm:h-10 sm:max-w-[200px]"></a>
            <div class="max-w-md">
                <img src="{{ asset('images/jobseeker/status-3d.svg') }}" alt="" class="mb-3 w-14 drop-shadow-lg sm:mb-4 sm:w-20 lg:mb-8 lg:w-40">
                <h2 class="hidden text-4xl font-semibold leading-tight tracking-tight text-white lg:block">Siapkan ruang kerja perusahaan Anda.</h2>
                <p class="hidden text-base leading-relaxed text-white/90 lg:mt-6 lg:block">Buat akun, lengkapi profil perusahaan, lalu kelola lowongan dan lamaran dari satu tempat.</p>
                @if ($registrationStep ?? null)
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
