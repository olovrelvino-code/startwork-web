@extends('layouts.public')

@section('title', 'Tentang StartWork')

@section('content')
    <section class="bg-blue-600 text-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,.8fr)] lg:items-end lg:px-8">
            <div>
                <h1 class="max-w-2xl text-4xl font-bold tracking-tight sm:text-5xl">Tentang StartWork</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8">StartWork mempertemukan pencari kerja dan perusahaan melalui lowongan yang dapat dijelajahi secara terbuka.</p>
            </div>
            <p class="max-w-md text-sm leading-7 text-white">Di satu tempat, pencari kerja dapat menelusuri posisi, menyiapkan profil dan CV, serta memantau lamaran yang dikirim melalui platform.</p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-14 px-4 py-14 sm:px-6 lg:px-8">
        <section class="grid gap-7 md:grid-cols-[minmax(0,.8fr)_minmax(0,1.2fr)]">
            <h2 class="text-2xl font-bold tracking-tight">Tujuan dan arah kami</h2>
            <div class="space-y-5 text-sm leading-7 text-slate-700">
                <p>Tujuan StartWork adalah membuat informasi lowongan dan langkah melamar lebih mudah ditemukan. Halaman publik menampilkan lowongan aktif dan profil perusahaan yang tersedia.</p>
                <p>Visi dan misi berikut merupakan konten pengenalan demo: menghadirkan pengalaman mencari kerja yang jelas bagi pencari kerja dan ruang pengelolaan rekrutmen yang tertata bagi perusahaan.</p>
            </div>
        </section>

        <section aria-labelledby="about-benefits">
            <h2 id="about-benefits" class="text-2xl font-bold tracking-tight">Untuk siapa StartWork dibuat?</h2>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <h3 class="text-xl font-semibold">Pencari kerja</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-700">Saring lowongan, kenali perusahaan, lengkapi profil dan CV, lalu lihat status lamaran internal dari akun Anda.</p>
                    <a href="{{ route('jobs.index') }}" class="mt-5 inline-block text-sm font-semibold text-blue-700 hover:underline">Jelajahi lowongan →</a>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-6">
                    <h3 class="text-xl font-semibold">Perusahaan</h3>
                    <p class="mt-3 text-sm leading-7 text-slate-700">Kelola informasi perusahaan, lowongan, dan pelamar melalui ruang Employer sesuai hak akses akun.</p>
                    <a href="{{ route('employer.register') }}" class="mt-5 inline-block text-sm font-semibold text-blue-700 hover:underline">Daftar sebagai Employer →</a>
                </div>
            </div>
        </section>

        <section aria-labelledby="about-steps">
            <h2 id="about-steps" class="text-2xl font-bold tracking-tight">Cara menggunakan StartWork</h2>
            <ol class="mt-6 grid gap-5 md:grid-cols-3">
                <li class="rounded-xl bg-white p-6"><h3 class="font-semibold">Temukan posisi</h3><p class="mt-2 text-sm leading-7 text-slate-700">Cari dan saring lowongan aktif sesuai kebutuhan Anda.</p></li>
                <li class="rounded-xl bg-white p-6"><h3 class="font-semibold">Siapkan profil</h3><p class="mt-2 text-sm leading-7 text-slate-700">Lengkapi data diri, skill, dan CV sebelum mengirim lamaran internal.</p></li>
                <li class="rounded-xl bg-white p-6"><h3 class="font-semibold">Pantau lamaran</h3><p class="mt-2 text-sm leading-7 text-slate-700">Lihat status lamaran internal yang telah dikirim melalui akun Jobseeker.</p></li>
            </ol>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8">
            <h2 class="text-2xl font-bold tracking-tight">Nilai yang ingin kami jaga</h2>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-700">Kejelasan informasi, kendali atas profil pribadi, dan akses yang sesuai peran adalah arah konten demo halaman ini. Tidak ada angka atau klaim pencapaian yang ditampilkan sebagai data nyata.</p>
        </section>
    </div>
@endsection
