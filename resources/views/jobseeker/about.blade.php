@extends('layouts.public')

@section('title', 'Tentang StartWork')

@section('content')
    <section class="relative isolate overflow-hidden bg-[#183047] text-white">
        <div aria-hidden="true" class="absolute inset-0 -z-10 bg-[linear-gradient(110deg,rgba(24,48,71,.82),rgba(24,48,71,.58)),url('/images/asset/13569.webp')] bg-cover bg-center"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-32 -z-10 h-96 w-96 rounded-full bg-[#B85B12]/35 blur-3xl sm:h-[32rem] sm:w-[32rem]"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-48 left-1/4 -z-10 h-80 w-80 rounded-full bg-[#315C7A]/50 blur-3xl"></div>
        <div class="relative mx-auto flex min-h-[28rem] max-w-7xl items-center px-4 py-16 sm:min-h-[34rem] sm:px-6 sm:py-20 lg:px-8">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.18em] text-blue-200">Tentang StartWork</p>
                <h1 class="mt-5 max-w-4xl text-4xl font-bold leading-tight tracking-tight sm:text-6xl">Temukan Peluang.<br class="hidden sm:block"> Bangun Masa Depan.</h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-blue-50">StartWork adalah platform yang menghubungkan pencari kerja dengan perusahaan melalui peluang kerja yang dapat dijelajahi.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('jobs.index') }}" class="inline-flex min-h-11 items-center justify-center bg-white px-5 py-3 text-sm font-bold text-[#183047] hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Jelajahi lowongan</a>
                    <a href="{{ route('employer.register') }}" class="inline-flex min-h-11 items-center justify-center border border-white/70 px-5 py-3 text-sm font-bold text-white hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Gabung sebagai Employer</a>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-14 px-4 py-12 sm:px-6 sm:py-14 lg:px-8">
        <section class="grid gap-8 lg:grid-cols-2 lg:gap-16">
            <div><p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Cerita kami</p><h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Pencarian kerja lebih terarah.</h2></div>
            <p class="border-l-2 border-amber-400 pl-6 text-base leading-8 text-slate-700 sm:pl-8">Menemukan peluang yang sesuai dan menjangkau kandidat yang tepat bisa terasa rumit. StartWork membantu mempertemukan kedua kebutuhan itu melalui informasi lowongan dan profil perusahaan dalam satu platform.</p>
        </section>

        <section aria-labelledby="about-mission" class="grid gap-8 border-y border-slate-200 py-8 sm:grid-cols-2 sm:gap-12">
            <article><span class="text-sm font-bold uppercase tracking-widest text-blue-700">Misi</span><h2 id="about-mission" class="mt-3 text-2xl font-bold">Mempermudah akses peluang.</h2><p class="mt-3 text-sm leading-7 text-slate-700">Membantu pencari kerja menemukan lowongan dan perusahaan menjangkau kandidat melalui proses yang lebih mudah dijelajahi.</p></article>
            <article class="sm:border-l sm:border-slate-200 sm:pl-12"><span class="text-sm font-bold uppercase tracking-widest text-amber-700">Visi</span><h2 class="mt-3 text-2xl font-bold">Ekosistem kerja yang terarah.</h2><p class="mt-3 text-sm leading-7 text-slate-700">Membangun ekosistem pencarian kerja yang lebih terarah bagi pencari kerja dan perusahaan.</p></article>
        </section>

        <section aria-labelledby="about-what-we-do" class="grid gap-8 lg:grid-cols-[.7fr_1.3fr] lg:gap-16">
            <div><p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Peran StartWork</p><h2 id="about-what-we-do" class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Menghubungkan dua kebutuhan.</h2></div>
            <div class="divide-y divide-slate-200 border-y border-slate-200 sm:grid sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                <article class="py-6 sm:py-3 sm:pr-8"><h3 class="text-xl font-bold">Jobseeker</h3><p class="mt-3 flex-1 text-sm leading-7 text-slate-700">Temukan dan jelajahi peluang kerja, kenali perusahaan, lalu siapkan profil dan CV Anda.</p><a href="{{ route('jobs.index') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-blue-800 hover:underline">Jelajahi lowongan <span aria-hidden="true">→</span></a></article>
                <article class="py-6 sm:py-3 sm:pl-8"><h3 class="text-xl font-bold">Employer</h3><p class="mt-3 flex-1 text-sm leading-7 text-slate-700">Publikasikan lowongan dan jangkau kandidat melalui ruang pengelolaan perusahaan.</p><a href="{{ route('employer.register') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-blue-800 hover:underline">Daftar sebagai Employer <span aria-hidden="true">→</span></a></article>
            </div>
        </section>

        <section aria-labelledby="about-values"><p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Prinsip kami</p><h2 id="about-values" class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Nilai yang memandu.</h2><div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([['Accessibility', 'Peluang kerja lebih mudah dijelajahi.'], ['Transparency', 'Informasi lowongan disajikan dengan jelas.'], ['Growth', 'Profil membantu pencari kerja menunjukkan potensinya.'], ['Connection', 'Mempertemukan pencari kerja dan perusahaan.']] as [$value, $description])
                <article class="border-t-2 border-blue-700 py-4 pr-4"><span aria-hidden="true" class="text-lg font-bold text-amber-600">✳</span><h3 class="mt-3 font-bold">{{ $value }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p></article>
            @endforeach
        </div></section>

        <section class="bg-[#183047] px-6 py-10 text-white sm:px-10 sm:py-12"><div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between"><div><p class="text-sm font-semibold uppercase tracking-widest text-amber-300">Mulai langkah berikutnya</p><h2 class="mt-3 text-3xl font-bold tracking-tight">Temukan peluang Anda di StartWork.</h2></div><div class="flex flex-col gap-3 sm:flex-row"><a href="{{ route('jobs.index') }}" class="inline-flex min-h-11 items-center justify-center bg-white px-5 py-3 text-sm font-bold text-[#183047] hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Gabung sebagai Jobseeker</a><a href="{{ route('employer.register') }}" class="inline-flex min-h-11 items-center justify-center border border-white/70 px-5 py-3 text-sm font-bold text-white hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Gabung sebagai Employer</a></div></div></section>
    </div>
@endsection
