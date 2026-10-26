<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Start Work</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/employer.js'])
</head>
<body class="admin-shell min-h-screen bg-white antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:p-3">Lewati navigasi</a>
    <div class="min-h-screen lg:grid lg:grid-cols-[minmax(320px,0.88fr)_minmax(0,1.12fr)]">
        <aside class="auth-visual auth-visual--employer hidden flex-col justify-between px-10 py-10 lg:flex xl:px-16" aria-label="Alur ruang kerja Employer">
            <a href="{{ url('/') }}" class="w-fit text-xl font-bold tracking-tight text-white" aria-label="Start Work, halaman utama">startwork<span class="text-[#E6A16D]">.</span></a>
            <div class="max-w-md">
                <h2 class="text-4xl font-semibold leading-tight tracking-tight text-white">Siapkan ruang kerja perusahaan Anda.</h2>
                <p class="mt-6 text-base leading-relaxed text-white/90">Buat akun, lengkapi profil perusahaan, lalu kelola lowongan dan lamaran dari satu tempat.</p>
                @if ($registrationStep ?? null)
                    <x-employer.registration-progress :step="$registrationStep" variant="dark" class="mt-10" />
                @endif
            </div>
            <p class="max-w-sm text-sm leading-relaxed text-white/90">Akses perusahaan dan pelamar dibatasi untuk akun Employer pemiliknya.</p>
        </aside>
        <main id="main-content" class="flex min-h-screen items-start px-5 py-8 sm:px-10 lg:items-center lg:px-16">
            <div class="mx-auto w-full @yield('content_width', 'max-w-[460px]')">
                <div class="mb-10 lg:hidden"><a href="{{ url('/') }}" class="text-xl font-bold tracking-tight text-slate-900">startwork<span class="text-[#B85B12]">.</span></a><p class="mt-1 text-sm text-slate-500">Ruang kerja Employer</p></div>
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
