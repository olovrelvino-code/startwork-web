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
        @endphp
        <form action="{{ route('jobs.index') }}" method="GET" role="search" data-job-search>
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="w-full min-w-0 sm:flex-1 lg:max-w-sm">
                    <label for="jobs-q" class="mb-2 block text-sm font-semibold">Nama Lowongan</label>
                    <div class="flex min-w-0 gap-2">
                        <input id="jobs-q" name="q" type="search" maxlength="255" value="{{ request('q') }}" placeholder="Cari judul lowongan" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                        <button type="submit" class="shrink-0 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Cari</button>
                    </div>
                </div>
                <div class="w-full sm:w-44 sm:shrink-0"><label for="jobs-sort" class="mb-2 block text-sm font-semibold">Urutkan</label><select id="jobs-sort" name="sort" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"><option value="latest" @selected(request('sort', 'latest') === 'latest')>Terbaru</option><option value="oldest" @selected(request('sort') === 'oldest')>Terlama</option><option value="a-z" @selected(request('sort') === 'a-z')>Judul A–Z</option><option value="z-a" @selected(request('sort') === 'z-a')>Judul Z–A</option></select></div>
            </div>
            <div class="grid items-start gap-8 lg:grid-cols-[260px_minmax(0,1fr)]">
                <aside class="grid gap-5 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2 lg:grid-cols-1" aria-label="Filter lowongan">
                    <div><label for="jobs-location" class="mb-2 block text-sm font-semibold">Lokasi</label><input id="jobs-location" name="location" type="search" maxlength="255" value="{{ request('location') }}" placeholder="Kota atau wilayah" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"></div>
                    <div class="relative" data-category-search>
                        <label for="jobs-category" class="mb-2 block text-sm font-semibold">Kategori</label>
                        <input id="jobs-category" name="category" type="search" maxlength="255" value="{{ request('category') }}" placeholder="Ketik kategori" autocomplete="off" role="combobox" aria-autocomplete="list" aria-haspopup="listbox" aria-controls="jobs-category-options" aria-expanded="false" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600">
                        <ul id="jobs-category-options" role="listbox" class="absolute left-0 right-0 z-20 mt-1 hidden max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                            @foreach ($categories as $category)
                                <li role="option" aria-selected="false" data-category-option data-category-value="{{ $category }}" tabindex="-1" class="cursor-pointer px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 focus:bg-slate-100 focus:outline-none">{{ $category }}</li>
                            @endforeach
                            <li data-category-empty class="hidden px-3 py-2 text-sm text-slate-600">Tidak ada kategori yang cocok.</li>
                        </ul>
                    </div>
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
                <section data-public-list="jobs" aria-live="polite" class="min-w-0">
                <p data-list-status class="mb-4 text-sm text-slate-600" role="status">Memuat lowongan…</p>
                <div data-list-items class="grid gap-4 md:grid-cols-2"></div>
                <nav data-list-pages class="mt-7 flex flex-wrap items-center gap-2" aria-label="Halaman lowongan"></nav>
                </section>
            </div>
        </form>
    </div>
    <template id="job-list-card"><a class="group block min-w-0 rounded-xl border border-slate-200 bg-white p-5 hover:border-blue-300 hover:bg-blue-50/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><div class="flex items-center gap-3"><div class="relative grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-lg border border-slate-200 bg-slate-100"><span data-field="logo-fallback" class="font-bold text-blue-700" aria-hidden="true"></span><img data-field="logo" class="absolute inset-0 hidden h-full w-full bg-white object-contain" alt=""></div><p data-field="company_name" class="min-w-0 break-words text-sm font-semibold text-blue-700"></p></div><h2 data-field="title" class="mt-4 text-lg font-semibold text-slate-900 group-hover:text-blue-700"></h2><p data-field="location" class="mt-3 text-sm text-slate-600"></p><p data-field="types" class="mt-2 text-xs text-slate-500"></p></a></template>
@endsection
