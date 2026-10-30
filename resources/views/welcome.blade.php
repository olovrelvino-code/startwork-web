@extends('layouts.public')

@section('title', 'Temukan pekerjaan yang tepat')
@section('description', 'Cari lowongan aktif dan temukan perusahaan di StartWork.')
@section('content')
    <section class="jobseeker-home-hero relative isolate overflow-hidden bg-slate-950 text-white" aria-labelledby="home-title">
        <div class="relative z-10 mx-auto flex min-h-[28rem] max-w-7xl flex-col justify-center px-4 py-16 sm:min-h-[31rem] sm:px-6 sm:py-20 lg:px-8 lg:py-24">
            <div class="mx-auto w-full max-w-6xl text-center">
                <h1 id="home-title" class="text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:whitespace-nowrap lg:text-[clamp(2.25rem,3.1vw,3rem)]"><span class="jobseeker-hero-count text-[var(--jobseeker-accent)]" data-hero-count aria-hidden="true">89,95%</span><span class="sr-only">89,95%</span> Peluang Menuju Karier Impianmu!</h1>
                <p class="jobseeker-hero-description mx-auto mt-5 max-w-2xl text-base leading-7 text-slate-200 sm:text-lg">Temukan pekerjaan yang tepat, kenali perusahaan pilihanmu, dan mulai perjalanan kariermu bersama StartWork.</p>
            </div>
            <form action="{{ route('jobs.index') }}" method="GET" role="search" aria-label="Cari lowongan" class="mx-auto mt-10 flex w-full max-w-4xl items-center gap-2 rounded-xl bg-white p-2">
                <svg aria-hidden="true" class="ml-2 h-5 w-5 shrink-0 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" /></svg>
                <div class="flex min-w-0 flex-1 flex-col sm:flex-row">
                    <div class="min-w-0 flex-1"><label for="home-search" class="sr-only">Nama lowongan</label><input id="home-search" name="q" type="search" maxlength="255" placeholder="Nama lowongan" class="min-h-11 w-full min-w-0 border-0 bg-white px-2 text-sm text-slate-900 placeholder:text-slate-500 focus:ring-0 sm:text-base"></div>
                    <div class="flex min-w-0 flex-1 items-center border-t border-slate-200 sm:border-l sm:border-t-0"><svg aria-hidden="true" class="ml-2 h-5 w-5 shrink-0 text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg><label for="home-location" class="sr-only">Lokasi</label><input id="home-location" name="location" type="search" maxlength="255" placeholder="Kota atau lokasi" class="min-h-11 w-full min-w-0 border-0 bg-white px-2 text-sm text-slate-900 placeholder:text-slate-500 focus:ring-0 sm:text-base"></div>
                </div>
                <button type="submit" class="jobseeker-home-search-button min-h-12 min-w-24 shrink-0 rounded-lg px-6 py-3 text-sm font-bold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 sm:min-w-28 sm:px-8">Cari</button>
            </form>
        </div>
    </section>

    <section class="bg-white py-16 sm:py-20" aria-labelledby="home-features-title">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl"><h2 id="home-features-title" class="text-3xl font-bold tracking-tight">Dari pencarian hingga lamaran.</h2><p class="mt-3 text-slate-600">Tiga langkah yang sudah tersedia di StartWork untuk membantu Anda bergerak maju.</p></div>
            <div class="mt-9 grid gap-4 md:grid-cols-3">
                <a href="{{ route('jobs.index') }}" class="group flex flex-col items-center rounded-xl border border-slate-200 bg-white p-6 text-center transition-colors hover:border-blue-300 hover:bg-blue-50/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><span class="grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-blue-700"><svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg></span><h3 class="mt-5 text-xl font-semibold group-hover:text-blue-700">Cari lowongan aktif</h3><p class="mt-3 text-sm leading-6 text-slate-600">Saring posisi berdasarkan kata kunci, lokasi, dan kategori yang tersedia.</p></a>
                <a href="{{ route('jobseeker.profile') }}" class="group flex flex-col items-center rounded-xl border border-slate-200 bg-white p-6 text-center transition-colors hover:border-blue-300 hover:bg-blue-50/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><span class="grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-blue-700"><svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3"/><rect x="8" y="2" width="8" height="4" rx="1"/><circle cx="12" cy="11" r="2"/><path d="M8 18c.5-2 2-3 4-3s3.5 1 4 3"/></svg></span><h3 class="mt-5 text-xl font-semibold group-hover:text-blue-700">Lengkapi profil &amp; CV</h3><p class="mt-3 text-sm leading-6 text-slate-600">Simpan data diri, keahlian, dan CV sebelum mengirim lamaran internal.</p></a>
                <a href="{{ route('jobseeker.applications') }}" class="group flex flex-col items-center rounded-xl border border-slate-200 bg-white p-6 text-center transition-colors hover:border-blue-300 hover:bg-blue-50/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><span class="grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-blue-700"><svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="m8 10 2 2 4-4M8 17h8"/></svg></span><h3 class="mt-5 text-xl font-semibold group-hover:text-blue-700">Ikuti status lamaran</h3><p class="mt-3 text-sm leading-6 text-slate-600">Lihat perkembangan lamaran yang Anda kirim melalui StartWork.</p></a>
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
                        <a href="{{ route('jobs.show', $job->id) }}" class="group flex min-w-0 cursor-pointer flex-col rounded-xl border border-slate-200 bg-white p-5 transition-[transform,box-shadow,border-color] duration-200 hover:-translate-y-1 hover:border-blue-300 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 motion-reduce:transform-none motion-reduce:transition-none">
                            <div class="flex items-center gap-3">
                                <div class="relative grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-lg bg-slate-100 text-2xl font-bold text-slate-700"><span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($job->company_name, 0, 1)) }}</span>@if ($job->company_logo)<img src="{{ url('/api/companies/'.$job->company_id.'/logo') }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain p-1" @@error="$el.remove()">@endif</div>
                                <p class="min-w-0 break-words text-sm font-semibold text-blue-700">{{ $job->company_name }}</p>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold leading-snug group-hover:text-blue-700">{{ $job->title }}</h3>
                            <p class="mt-3 text-sm text-slate-600">{{ $job->location ?: 'Lokasi tidak dicantumkan' }}</p>
                            @if ($job->category || $job->job_type || $job->work_type)
                                <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium leading-5">
                                    @if ($job->category)<span class="max-w-full break-words rounded-full bg-slate-100 px-3 py-1 text-blue-700">{{ $job->category }}</span>@endif
                                    @if ($job->job_type)<span class="max-w-full break-words rounded-full border border-slate-200 px-3 py-1 text-slate-700">{{ $job->job_type }}</span>@endif
                                    @if ($job->work_type)<span class="max-w-full break-words rounded-full border border-slate-200 px-3 py-1 text-slate-700">{{ $job->work_type }}</span>@endif
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="bg-white py-14 sm:py-16" aria-labelledby="partners-title">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 id="partners-title" class="text-2xl font-bold tracking-tight">Mitra Perusahaan</h2>
            <p class="mt-2 text-sm text-slate-600">Pratinjau tata letak. Logo dan daftar mitra resmi belum tersedia.</p>
            <div class="jobseeker-partners-window mt-8" aria-label="Contoh tempat untuk logo mitra perusahaan">
                <div class="jobseeker-partners-track">
                    @foreach ([false, true] as $duplicate)
                        <div class="jobseeker-partners-group" @if ($duplicate) aria-hidden="true" @endif>
                            @foreach (range(1, 6) as $number)
                                <div class="flex w-40 shrink-0 items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:w-48">
                                    <span aria-hidden="true" class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-white font-bold text-blue-700">M{{ $number }}</span>
                                    <span class="text-sm font-medium text-slate-600">Contoh logo {{ $number }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="bg-slate-100 py-14 sm:py-16" aria-labelledby="latest-articles-title">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4"><div><h2 id="latest-articles-title" class="text-2xl font-bold tracking-tight">Artikel Terbaru</h2><p class="mt-2 text-sm text-slate-600">Wawasan karier yang baru diterbitkan.</p></div><a href="{{ route('articles.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">Lihat semua artikel <span aria-hidden="true">→</span></a></div>
            @if ($articles->isEmpty())
                <p class="rounded-xl border border-slate-200 bg-white p-6 text-slate-600">Belum ada artikel yang diterbitkan.</p>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($articles as $article)
                        <a href="{{ route('articles.show', $article->slug) }}" class="group flex min-w-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white transition-colors hover:border-blue-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <div class="relative flex aspect-[12/7] items-center justify-center bg-slate-50 text-sm font-semibold text-slate-500">
                                <span>Artikel</span>
                                @if ($article->cover_image)
                                    <img src="{{ route('articles.cover', $article->slug) }}" alt="" loading="lazy" width="720" height="420" class="absolute inset-0 h-full w-full object-cover" @@error="$el.remove()">
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                @if ($article->published_at)<time class="text-xs font-semibold uppercase tracking-wider text-slate-500" datetime="{{ \Illuminate\Support\Carbon::parse($article->published_at)->toDateString() }}">{{ \Illuminate\Support\Carbon::parse($article->published_at)->translatedFormat('d F Y') }}</time>@endif
                                <h3 class="mt-3 text-lg font-bold leading-snug group-hover:text-blue-700">{{ $article->title }}</h3>
                                @if ($article->excerpt)<p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ $article->excerpt }}</p>@endif
                            </div>
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

@endsection
