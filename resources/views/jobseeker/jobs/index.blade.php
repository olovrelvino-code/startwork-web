@extends('layouts.public')

@section('title', 'Lowongan kerja')

@section('content')
    <section class="jobseeker-jobs-banner bg-[var(--jobseeker-primary-dark)] text-white" data-jobs-banner aria-labelledby="jobs-banner-title">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
            <div class="max-w-3xl border-l-4 border-[var(--jobseeker-accent)] pl-5">
                <h1 id="jobs-banner-title" class="text-3xl font-bold tracking-tight sm:text-4xl">Peluangmu Dimulai di Sini.</h1>
                <p class="mt-3 text-sm leading-7 text-slate-200 sm:text-base">Temukan pekerjaan yang tepat dan ambil langkah pertama menuju karier impian bersama StartWork.</p>
            </div>
        </div>
    </section>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-8"><h2 class="text-2xl font-bold tracking-tight">Lowongan kerja</h2><p class="mt-2 text-slate-600">Cari peluang aktif dan temukan posisi yang sesuai.</p></header>
        @php
            $selectedJobTypes = (array) request()->query('job_type', []);
            $selectedWorkTypes = (array) request()->query('work_type', []);
            $selectedCategories = (array) request()->query('category', []);
        @endphp
        <form action="{{ route('jobs.index') }}" method="GET" role="search" data-job-search class="grid items-start gap-y-5 lg:grid-cols-[260px_minmax(0,1fr)] lg:grid-rows-[auto_1fr] lg:gap-x-8">
            <div class="grid min-w-0 gap-4 sm:grid-cols-[minmax(0,1fr)_11rem] sm:items-end lg:col-start-2 lg:row-start-1">
                <div class="min-w-0"><label for="jobs-q" class="mb-2 block text-sm font-semibold">Nama Lowongan</label><div class="relative min-w-0"><input id="jobs-q" name="q" type="search" maxlength="255" value="{{ request('q') }}" placeholder="Cari judul lowongan" class="min-h-11 w-full min-w-0 rounded-lg border-slate-300 pr-12 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"><button type="submit" aria-label="Cari lowongan" title="Cari lowongan" class="absolute right-1 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-md bg-[#B85B12] text-white transition-colors hover:bg-[#8A430A] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 4.2 4.2"></path></svg></button></div></div>
                <div class="min-w-0"><label for="jobs-sort" class="mb-2 block text-sm font-semibold">Urutkan</label><select id="jobs-sort" name="sort" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"><option value="latest" @selected(request('sort', 'latest') === 'latest')>Terbaru</option><option value="oldest" @selected(request('sort') === 'oldest')>Terlama</option><option value="a-z" @selected(request('sort') === 'a-z')>Judul A–Z</option><option value="z-a" @selected(request('sort') === 'z-a')>Judul Z–A</option></select></div>
            </div>
                <aside class="grid gap-5 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2 lg:col-start-1 lg:row-span-2 lg:row-start-1 lg:grid-cols-1" aria-label="Filter lowongan">
                    <div><label for="jobs-location" class="mb-2 block text-sm font-semibold">Lokasi</label><input id="jobs-location" name="location" type="search" maxlength="255" value="{{ request('location') }}" placeholder="Kota atau wilayah" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"></div>
                    <fieldset class="space-y-2"><legend class="mb-2 text-sm font-semibold">Kategori</legend>
                        @foreach (\App\JobCategory::PREDEFINED as $category)
                            <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="category[]" value="{{ $category }}" @checked(in_array($category, $selectedCategories, true)) class="mt-0.5 rounded border-slate-300 text-blue-600">{{ $category }}</label>
                        @endforeach
                    </fieldset>
                    <fieldset data-job-types class="space-y-2"><legend class="mb-2 text-sm font-semibold">Tipe pekerjaan</legend>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" data-all-types @checked(count($selectedJobTypes) === 0) class="rounded border-slate-300 text-blue-600">Semua kategori</label>
                        @forelse ($jobTypes->filter() as $type)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="job_type[]" value="{{ $type }}" @checked(in_array($type, $selectedJobTypes, true)) class="rounded border-slate-300 text-blue-600">{{ $type }}</label>
                        @empty
                            <p class="text-sm text-slate-600">Belum ada tipe pekerjaan aktif.</p>
                        @endforelse
                    </fieldset>
                    <fieldset class="space-y-2"><legend class="mb-2 text-sm font-semibold">Sistem kerja</legend>
                        @foreach (['On-site', 'Hybrid', 'Remote', 'Work From Home'] as $type)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="work_type[]" value="{{ $type }}" @checked(in_array($type, $selectedWorkTypes, true)) class="rounded border-slate-300 text-blue-600">{{ $type }}</label>
                        @endforeach
                    </fieldset>
                    <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-1"><button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Terapkan</button><a href="{{ route('jobs.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Reset</a></div>
                </aside>
                <section data-public-list="jobs" aria-live="polite" class="min-w-0 lg:col-start-2 lg:row-start-2">
                <p data-list-status class="mb-4 text-sm text-slate-600" role="status">Memuat lowongan…</p>
                <div data-list-items class="grid gap-4 md:grid-cols-2"></div>
                <nav data-list-pages class="mt-7 flex flex-wrap items-center gap-2" aria-label="Halaman lowongan"></nav>
                </section>
        </form>
    </div>
    <template id="job-list-card"><article class="relative min-w-0 rounded-xl border border-slate-200 bg-white p-5 hover:border-blue-300"><div class="absolute right-3 top-3 z-10 flex gap-1"><button type="button" data-save-job aria-label="Simpan lowongan" title="Simpan lowongan" class="jobseeker-save-button rounded-full p-2 focus-visible:outline focus-visible:outline-2"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M6 3.5h12v17l-6-4-6 4z"/></svg></button><button type="button" data-share-job aria-label="Bagikan lowongan" title="Bagikan lowongan" class="rounded-full p-2 text-blue-700 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="19" r="2.5"/><path d="m8.3 10.7 7.4-4.4M8.3 13.3l7.4 4.4"/></svg></button></div><a data-job-link class="group block pr-14 focus-visible:outline focus-visible:outline-2"><div class="flex items-center gap-3"><div class="relative grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-lg border border-slate-200 bg-slate-100"><span data-field="logo-fallback" class="font-bold text-blue-700" aria-hidden="true"></span><img data-field="logo" class="absolute inset-0 hidden h-full w-full bg-white object-contain" alt=""></div><p data-field="company_name" class="min-w-0 break-words text-sm font-semibold text-blue-700"></p></div><h2 data-field="title" class="mt-4 text-lg font-semibold text-slate-900 group-hover:text-blue-700"></h2><p data-field="location" class="mt-3 text-sm text-slate-600"></p><div data-field="types" class="mt-4 flex flex-wrap gap-2 text-xs font-medium leading-5"></div></a></article></template>
    <p data-save-feedback role="status" class="mt-3 text-sm text-slate-600" aria-live="polite"></p>
@endsection
