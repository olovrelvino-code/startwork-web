@extends('layouts.public')

@section('title', 'Temukan pekerjaan yang tepat')
@section('description', 'Cari lowongan aktif dan temukan perusahaan di StartWork.')
@section('content')
    <section class="jobseeker-home-hero relative isolate overflow-hidden bg-slate-950 text-white" aria-labelledby="home-title">
        <div class="relative z-10 mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-4xl text-center">
                <h1 id="home-title" class="text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-6xl">Langkah kerja berikutnya.</h1>
                <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-slate-200 sm:text-lg">Jelajahi lowongan aktif, kenali perusahaan, dan siapkan profil untuk melamar dari satu tempat.</p>
            </div>
            <form action="{{ route('jobs.index') }}" method="GET" role="search" aria-label="Cari lowongan" class="mx-auto mt-10 flex max-w-3xl items-center gap-2 rounded-xl bg-white p-2 focus-within:ring-2 focus-within:ring-[var(--jobseeker-accent)]">
                <svg aria-hidden="true" class="ml-2 h-5 w-5 shrink-0 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" /></svg>
                <label for="home-search" class="sr-only">Cari nama atau judul lowongan</label>
                <input id="home-search" name="q" type="search" maxlength="255" placeholder="Cari nama atau judul lowongan" class="min-h-12 min-w-0 flex-1 border-0 bg-white px-2 text-sm text-slate-900 placeholder:text-slate-500 focus:ring-0 sm:text-base">
                <button type="submit" class="jobseeker-home-search-button min-h-12 shrink-0 rounded-lg px-4 py-3 text-sm font-bold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 sm:px-6">Cari</button>
            </form>
            <div class="mx-auto mt-7 max-w-5xl" aria-labelledby="popular-categories-title">
                <h2 id="popular-categories-title" class="text-center text-sm font-semibold text-white">Kategori lowongan aktif</h2>
                @if ($categories->isEmpty())
                    <p class="mt-3 text-center text-sm text-slate-200">Kategori akan tampil saat ada lowongan aktif.</p>
                @else
                    <div class="mt-3 flex flex-wrap justify-center gap-2">
                        @foreach ($categories as $category)
                            <a href="{{ route('jobs.index', ['category' => $category->category]) }}" class="rounded-full bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">{{ $category->category }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
            <dl class="mx-auto mt-9 flex max-w-xl flex-wrap items-center justify-center gap-x-9 gap-y-3 text-center text-sm text-slate-200"><div><dt class="inline">Lowongan aktif</dt><dd class="ml-2 inline font-semibold tabular-nums text-white">{{ number_format($activeJobCount, 0, ',', '.') }}</dd></div><div><dt class="inline">Perusahaan terdaftar</dt><dd class="ml-2 inline font-semibold tabular-nums text-white">{{ number_format($companyCount, 0, ',', '.') }}</dd></div></dl>
        </div>
    </section>

    <section class="bg-white py-16 sm:py-20" aria-labelledby="home-features-title">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl"><h2 id="home-features-title" class="text-3xl font-bold tracking-tight">Dari pencarian hingga lamaran.</h2><p class="mt-3 text-slate-600">Tiga langkah yang sudah tersedia di StartWork untuk membantu Anda bergerak maju.</p></div>
            <div class="mt-9 grid gap-4 md:grid-cols-3">
                <a href="{{ route('jobs.index') }}" class="group rounded-xl border border-slate-200 bg-white p-6 transition-colors hover:border-blue-300 hover:bg-blue-50/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><h3 class="text-xl font-semibold group-hover:text-blue-700">Cari lowongan aktif</h3><p class="mt-3 text-sm leading-6 text-slate-600">Saring posisi berdasarkan kata kunci, lokasi, dan kategori yang tersedia.</p></a>
                <a href="{{ route('jobseeker.profile') }}" class="group rounded-xl border border-slate-200 bg-white p-6 transition-colors hover:border-blue-300 hover:bg-blue-50/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><h3 class="text-xl font-semibold group-hover:text-blue-700">Lengkapi profil &amp; CV</h3><p class="mt-3 text-sm leading-6 text-slate-600">Simpan data diri, keahlian, dan CV sebelum mengirim lamaran internal.</p></a>
                <a href="{{ route('jobseeker.applications') }}" class="group rounded-xl border border-slate-200 bg-white p-6 transition-colors hover:border-blue-300 hover:bg-blue-50/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><h3 class="text-xl font-semibold group-hover:text-blue-700">Ikuti status lamaran</h3><p class="mt-3 text-sm leading-6 text-slate-600">Lihat perkembangan lamaran yang Anda kirim melalui StartWork.</p></a>
            </div>
        </div>
    </section>

    <section class="bg-slate-100 py-16 sm:py-20" aria-labelledby="latest-jobs-title">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4"><div><h2 id="latest-jobs-title" class="text-2xl font-bold tracking-tight">Lowongan terbaru</h2><p class="mt-2 text-sm text-slate-600">Peluang aktif yang baru ditambahkan.</p></div><a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">Lihat daftar lengkap →</a></div>
            @if ($jobs->isEmpty())
                <p class="rounded-xl border border-slate-200 bg-white p-6 text-slate-600">Belum ada lowongan aktif saat ini.</p>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($jobs as $job)
                        <a href="{{ route('jobs.show', $job->id) }}" class="group flex min-h-[17rem] min-w-0 cursor-pointer flex-col rounded-xl border border-slate-200 bg-white p-5 transition-[transform,box-shadow,border-color] duration-200 hover:-translate-y-1 hover:border-blue-300 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 motion-reduce:transform-none motion-reduce:transition-none sm:aspect-[1.15]">
                            <div class="flex items-center gap-3">
                                <div class="relative grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-lg bg-slate-100 text-lg font-bold text-slate-700"><span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($job->company_name, 0, 1)) }}</span>@if ($job->company_logo)<img src="{{ url('/api/companies/'.$job->company_id.'/logo') }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain p-1" @@error="$el.remove()">@endif</div>
                                <p class="min-w-0 break-words text-sm font-semibold text-blue-700">{{ $job->company_name }}</p>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold leading-snug group-hover:text-blue-700">{{ $job->title }}</h3><div class="mt-auto pt-4"><p class="text-sm text-slate-600">{{ $job->location ?: 'Lokasi tidak dicantumkan' }}</p><p class="mt-1 text-xs text-slate-500">{{ implode(' · ', array_filter([$job->job_type, $job->work_type])) }}</p></div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="bg-white py-14 sm:py-16" aria-labelledby="testimonials-title">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4"><div><h2 id="testimonials-title" class="text-2xl font-bold tracking-tight">Cerita pengguna</h2><p class="mt-2 text-sm text-slate-600">Contoh tampilan — seluruh cerita di bawah adalah data demo, bukan ulasan pengguna nyata.</p></div><a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">Jelajahi lowongan →</a></div>
            @php
                $demoTestimonials = [
                    ['name' => 'Nabila Putri', 'role' => 'Pencari kerja', 'quote' => 'Saya bisa membandingkan posisi dan lokasi lowongan dari satu halaman.'],
                    ['name' => 'Rizky Pratama', 'role' => 'Lulusan baru', 'quote' => 'Informasi perusahaan membantu saya mengenali tempat kerja sebelum melamar.'],
                    ['name' => 'Dewi Anggraini', 'role' => 'Pencari kerja', 'quote' => 'Status lamaran yang tersimpan memudahkan saya mengikuti proses berikutnya.'],
                ];
            @endphp
            <div class="mt-8 grid gap-4 md:grid-cols-3">
                @foreach ($demoTestimonials as $testimonial)
                    <figure class="flex h-full flex-col rounded-xl border border-slate-200 bg-slate-50 p-6">
                        <blockquote class="flex-1 text-sm leading-7 text-slate-700">“{{ $testimonial['quote'] }}”</blockquote>
                        <figcaption class="mt-6 flex items-center gap-3 border-t border-slate-200 pt-4"><span aria-hidden="true" class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100 font-semibold text-blue-700">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($testimonial['name'], 0, 1)) }}</span><span class="min-w-0"><strong class="block text-sm text-slate-900">{{ $testimonial['name'] }}</strong><span class="block text-xs text-slate-600">{{ $testimonial['role'] }} · Data demo</span></span></figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-slate-100 py-16 sm:py-20" aria-labelledby="top-companies-title" x-data="{ atStart: true, atEnd: true, update() { const track = this.$refs.track; this.atStart = track.scrollLeft < 4; this.atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4; } }" x-init="$nextTick(() => update())" @resize.window="update()">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4"><div><h2 id="top-companies-title" class="text-3xl font-bold tracking-tight">Perusahaan paling aktif</h2><p class="mt-3 text-slate-600">Diurutkan menurut jumlah lowongan aktif.</p></div>@if ($companies->count() > 1)<div class="flex gap-2"><button type="button" aria-label="Geser perusahaan ke kiri" :disabled="atStart" @click="$refs.track.scrollBy({ left: -280 })" class="grid h-11 w-11 place-items-center rounded-full border border-slate-300 bg-white text-lg hover:border-blue-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:cursor-not-allowed disabled:opacity-40">←</button><button type="button" aria-label="Geser perusahaan ke kanan" :disabled="atEnd" @click="$refs.track.scrollBy({ left: 280 })" class="grid h-11 w-11 place-items-center rounded-full border border-slate-300 bg-white text-lg hover:border-blue-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:cursor-not-allowed disabled:opacity-40">→</button></div>@endif</div>
            @if ($companies->isEmpty())
                <p class="mt-8 rounded-xl border border-slate-200 bg-white p-6 text-slate-600">Belum ada perusahaan dengan lowongan aktif.</p>
            @else
                <div x-ref="track" @scroll.passive="update()" tabindex="0" aria-label="Daftar perusahaan dengan lowongan aktif" class="mt-8 flex snap-x snap-mandatory gap-4 overflow-x-auto pb-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    @foreach ($companies as $company)
                        <a href="{{ route('companies.show', $company->id) }}" class="group flex w-[245px] shrink-0 snap-start flex-col rounded-xl border border-slate-200 bg-white p-5 transition-colors hover:border-blue-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 sm:w-[270px]">
                            <div class="relative grid h-20 w-20 place-items-center overflow-hidden rounded-lg bg-slate-100 text-2xl font-bold text-slate-700"><span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($company->name, 0, 1)) }}</span>@if ($company->logo)<img src="{{ url('/api/companies/'.$company->id.'/logo') }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain p-1" @@error="$el.remove()">@endif</div>
                            <h3 class="mt-4 line-clamp-2 text-lg font-semibold group-hover:text-blue-700">{{ $company->name }}</h3><p class="mt-2 text-sm text-slate-600">{{ $company->location ?: 'Lokasi tidak dicantumkan' }}</p><p class="mt-auto pt-5 text-sm font-semibold text-blue-700">{{ number_format($company->open_jobs, 0, ',', '.') }} lowongan aktif</p>
                        </a>
                    @endforeach
                </div>
            @endif
            <a href="{{ route('companies.index') }}" class="mt-5 inline-block text-sm font-semibold text-blue-700 hover:underline">Lihat semua perusahaan →</a>
        </div>
    </section>

@endsection
