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
            <main id="main-content" class="grid flex-1 items-start gap-7 py-7 sm:gap-10 sm:py-10 lg:grid-cols-[minmax(0,1fr)_minmax(360px,460px)] lg:items-stretch lg:gap-12">
                <section class="order-2 mx-auto w-full max-w-xl lg:order-1 @if (request()->routeIs('login')) hidden md:block @endif" aria-label="Peluang kerja StartWork">
                    <div data-role-visual="jobseeker" class="auth-promo-panel">
                        <img src="{{ asset('images/asset/jobseeker.webp') }}" alt="Tim profesional berkolaborasi di kantor">
                        <div class="auth-promo-content">
                            <h1>Temukan pekerjaan yang tepat untuk langkah berikutnya.</h1>
                            <p>Cari peluang, siapkan profil, dan ikuti perkembangan lamaran dalam satu akun.</p>
                            <ul class="auth-promo-benefits" aria-label="Manfaat untuk Jobseeker">
                                <li>Lowongan dari perusahaan terverifikasi</li>
                                <li>Profil dan CV tersimpan dalam satu akun</li>
                                <li>Pantau proses lamaran dengan mudah</li>
                            </ul>
                        </div>
                    </div>
                    <div data-role-visual="employer" class="auth-promo-panel hidden" hidden>
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
