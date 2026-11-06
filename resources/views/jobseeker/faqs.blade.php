@extends('layouts.public')

@section('title', 'Pertanyaan umum')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <header class="max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight">Pertanyaan umum</h1>
            <p class="mt-3 text-slate-600">Jawaban ringkas tentang akun, pencarian, lamaran, dan perusahaan di StartWork.</p>
        </header>

        <div class="mt-9 grid gap-x-10 gap-y-9 md:grid-cols-2">
            <section aria-labelledby="faq-account">
                <h2 id="faq-account" class="mb-4 text-xl font-semibold">Akun</h2>
                <div class="space-y-3">
                    <details class="jobseeker-faq rounded-xl border border-slate-200 bg-white p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">Apakah perlu akun untuk melihat lowongan?<span class="jobseeker-faq-indicator text-xl" aria-hidden="true">+</span></summary><p class="pt-3 text-sm leading-7 text-slate-600">Tidak. Daftar lowongan dan profil perusahaan dapat dibuka tanpa masuk. Akun Jobseeker diperlukan untuk mengirim dan memantau lamaran internal.</p></details>
                    <details class="jobseeker-faq rounded-xl border border-slate-200 bg-white p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">Bagaimana jika lupa kata sandi?<span class="jobseeker-faq-indicator text-xl" aria-hidden="true">+</span></summary><p class="pt-3 text-sm leading-7 text-slate-600">Gunakan <a href="{{ route('password.request') }}" class="font-semibold text-blue-700 underline">halaman lupa kata sandi</a> untuk meminta tautan pemulihan melalui email.</p></details>
                </div>
            </section>
            <section aria-labelledby="faq-search">
                <h2 id="faq-search" class="mb-4 text-xl font-semibold">Pencarian lowongan</h2>
                <div class="space-y-3">
                    <details class="jobseeker-faq rounded-xl border border-slate-200 bg-white p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">Bagaimana menemukan lowongan yang sesuai?<span class="jobseeker-faq-indicator text-xl" aria-hidden="true">+</span></summary><p class="pt-3 text-sm leading-7 text-slate-600">Gunakan kata kunci, lokasi, kategori, tipe pekerjaan, sistem kerja, atau rentang tanggal pada <a href="{{ route('jobs.index') }}" class="font-semibold text-blue-700 underline">halaman lowongan</a>.</p></details>
                    <details class="jobseeker-faq rounded-xl border border-slate-200 bg-white p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">Mengapa suatu lowongan tidak ditemukan?<span class="jobseeker-faq-indicator text-xl" aria-hidden="true">+</span></summary><p class="pt-3 text-sm leading-7 text-slate-600">Daftar publik menampilkan lowongan yang aktif dan belum melewati tanggal kedaluwarsa. Coba kurangi filter jika hasil pencarian kosong.</p></details>
                </div>
            </section>
            <section aria-labelledby="faq-apply">
                <h2 id="faq-apply" class="mb-4 text-xl font-semibold">Lamaran</h2>
                <div class="space-y-3">
                    <details class="jobseeker-faq rounded-xl border border-slate-200 bg-white p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">Apa yang harus saya isi sebelum melamar?<span class="jobseeker-faq-indicator text-xl" aria-hidden="true">+</span></summary><p class="pt-3 text-sm leading-7 text-slate-600">Untuk lamaran internal, lengkapi data diri, setidaknya satu skill, dan unggah file CV. Foto profil bersifat opsional.</p></details>
                    <details class="jobseeker-faq rounded-xl border border-slate-200 bg-white p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">Di mana saya melihat status lamaran?<span class="jobseeker-faq-indicator text-xl" aria-hidden="true">+</span></summary><p class="pt-3 text-sm leading-7 text-slate-600">Buka Lamaran Saya setelah masuk. Statusnya dapat berupa Terkirim, Dalam proses, Diterima, atau Ditolak.</p></details>
                    <details class="jobseeker-faq rounded-xl border border-slate-200 bg-white p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">Mengapa tombol melamar membuka situs lain?<span class="jobseeker-faq-indicator text-xl" aria-hidden="true">+</span></summary><p class="pt-3 text-sm leading-7 text-slate-600">Sebagian lowongan menggunakan tautan pendaftaran perusahaan. Lamaran untuk lowongan itu tidak dikirim melalui StartWork.</p></details>
                </div>
            </section>
            <section aria-labelledby="faq-company">
                <h2 id="faq-company" class="mb-4 text-xl font-semibold">Perusahaan</h2>
                <div class="space-y-3">
                    <details class="jobseeker-faq rounded-xl border border-slate-200 bg-white p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">Dapatkah saya melihat profil perusahaan?<span class="jobseeker-faq-indicator text-xl" aria-hidden="true">+</span></summary><p class="pt-3 text-sm leading-7 text-slate-600">Ya. <a href="{{ route('companies.index') }}" class="font-semibold text-blue-700 underline">Daftar perusahaan</a> dan halaman profilnya dapat dibuka tanpa masuk.</p></details>
                    <details class="jobseeker-faq rounded-xl border border-slate-200 bg-white p-5"><summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">Bagaimana perusahaan mengelola lowongan?<span class="jobseeker-faq-indicator text-xl" aria-hidden="true">+</span></summary><p class="pt-3 text-sm leading-7 text-slate-600">Perusahaan menggunakan <a href="{{ route('employer.login') }}" class="font-semibold text-blue-700 underline">ruang Employer</a> sesuai hak akses akun mereka.</p></details>
                </div>
            </section>
        </div>
    </div>
@endsection
