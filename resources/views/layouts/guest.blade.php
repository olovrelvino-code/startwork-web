<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Akun Jobseeker · StartWork</title>
    <link rel="icon" type="image/png" href="{{ asset('images/asset/Logo Start Work favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="jobseeker-shell auth-login min-h-screen bg-white text-slate-900 antialiased">
    @if (isset($authSwitch))
        <div class="mx-auto flex min-h-screen max-w-6xl flex-col px-4 sm:px-6">
            <header class="flex items-center justify-between border-b border-slate-200 py-4 sm:py-5">
                <a href="{{ url('/') }}" class="rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" aria-label="StartWork, halaman utama"><img src="{{ asset('images/asset/Logo Start Work B transparan.png') }}" alt="StartWork" class="block h-9 w-auto max-w-[180px] object-contain sm:h-11 sm:max-w-[220px]"></a>
                <a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-slate-700 hover:text-blue-700">Jelajahi Lowongan</a>
            </header>
            <main id="main-content" class="grid flex-1 items-start gap-7 py-7 sm:gap-10 sm:py-10 lg:grid-cols-[minmax(0,1fr)_minmax(360px,460px)] lg:items-center lg:gap-12">
                <section class="hidden max-w-xl lg:block" aria-label="Peluang kerja Jobseeker">
                    <div data-role-visual="jobseeker"><img src="{{ asset('images/jobseeker/search-3d.svg') }}" alt="" class="mb-8 w-52 drop-shadow-lg"><p class="text-sm font-semibold text-blue-700">Ruang Jobseeker</p><h1 class="mt-4 text-4xl font-bold leading-tight tracking-tight">Satu tempat untuk peluang dan perjalanan karier Anda.</h1><p class="mt-5 text-base leading-7 text-slate-600">Temukan lowongan aktif, siapkan profil dan CV, lalu pantau status lamaran Anda.</p></div>
                    <div data-role-visual="employer" class="hidden" hidden><img src="{{ asset('images/jobseeker/status-3d.svg') }}" alt="" class="mb-8 w-52 drop-shadow-lg"><p class="text-sm font-semibold text-[#B85B12]">Ruang Employer</p><h1 class="mt-4 text-4xl font-bold leading-tight tracking-tight">Kelola lowongan dan temukan kandidat yang tepat.</h1><p class="mt-5 text-base leading-7 text-slate-600">Atur kebutuhan rekrutmen perusahaan dan pantau lamaran dalam satu ruang kerja.</p></div>
                </section>
                <div class="mx-auto w-full max-w-[460px] lg:mx-0">
                    {{ $authSwitch }}
                    {{ $slot }}
                </div>
            </main>
        </div>
    @else
    <div class="mx-auto flex min-h-screen max-w-6xl flex-col px-4 sm:px-6">
        <header class="jobseeker-auth-header flex items-center justify-between border-b border-[#B8CFDA] py-4 sm:py-5"><a href="{{ url('/') }}" class="rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" aria-label="StartWork, halaman utama"><img src="{{ asset('images/asset/Logo Start Work B transparan.png') }}" alt="StartWork" class="block h-9 w-auto max-w-[180px] object-contain sm:h-11 sm:max-w-[220px]"></a><a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-slate-700 hover:text-blue-700">Jelajahi lowongan</a></header>
        <main class="grid flex-1 items-center gap-7 py-7 sm:gap-10 sm:py-10 lg:grid-cols-[minmax(0,1fr)_minmax(360px,440px)] lg:gap-12">
            <div data-role-visual="jobseeker" class="flex items-center gap-4 sm:gap-6 lg:max-w-xl lg:flex-col lg:items-start"><img src="{{ asset('images/jobseeker/search-3d.svg') }}" alt="" class="w-16 shrink-0 drop-shadow-lg sm:w-20 lg:mb-8 lg:w-52"><div><p class="text-sm font-semibold text-[#A34F0D]">Ruang Jobseeker</p><h1 class="mt-2 text-xl font-bold leading-tight tracking-tight text-[#17394B] sm:text-2xl lg:mt-4 lg:text-4xl">Satu tempat untuk peluang dan perjalanan karier Anda.</h1><p class="mt-2 hidden text-base leading-7 text-slate-600 sm:block lg:mt-5">Temukan lowongan aktif, siapkan profil dan CV, lalu pantau status lamaran yang Anda kirim.</p></div></div>
            <div data-role-visual="employer" class="hidden flex items-center gap-4 sm:gap-6 lg:max-w-xl lg:flex-col lg:items-start" hidden><img src="{{ asset('images/jobseeker/status-3d.svg') }}" alt="" class="w-16 shrink-0 drop-shadow-lg sm:w-20 lg:mb-8 lg:w-52"><div><p class="text-sm font-semibold text-[#A34F0D]">Ruang Employer</p><h1 class="mt-2 text-xl font-bold leading-tight tracking-tight text-[#17394B] sm:text-2xl lg:mt-4 lg:text-4xl">Kelola lowongan dan temukan kandidat yang tepat.</h1><p class="mt-2 hidden text-base leading-7 text-slate-600 sm:block lg:mt-5">Atur kebutuhan rekrutmen perusahaan dan pantau lamaran dalam satu ruang kerja.</p></div></div>
            <div class="w-full min-w-0">
                {{ $authSwitch ?? '' }}
                {{ $slot }}
            </div>
        </main>
    </div>
    @endif
</body>
</html>
