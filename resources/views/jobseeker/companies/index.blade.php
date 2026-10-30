@extends('layouts.public')

@section('title', 'Perusahaan')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-8"><h1 class="text-3xl font-bold tracking-tight">Kenali perusahaan</h1><p class="mt-2 text-slate-600">Jelajahi profil perusahaan dan lowongan yang sedang dibuka.</p></header>
        <form action="{{ route('companies.index') }}" method="GET" role="search" class="mb-7 grid gap-3 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto_auto] sm:items-end">
            <div><label for="companies-q" class="mb-2 block text-sm font-semibold">Nama perusahaan</label><input id="companies-q" name="q" type="search" maxlength="255" value="{{ request('q') }}" placeholder="Cari perusahaan" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"></div>
            <div><label for="companies-location" class="mb-2 block text-sm font-semibold">Lokasi</label><input id="companies-location" name="location" type="search" maxlength="255" value="{{ request('location') }}" placeholder="Kota atau wilayah" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"></div>
            <div><label for="companies-sort" class="mb-2 block text-sm font-semibold">Urutkan</label><select id="companies-sort" name="sort" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-600 focus:ring-1 focus:ring-blue-600"><option value="latest" @selected(request('sort', 'latest') === 'latest')>Terbaru</option><option value="oldest" @selected(request('sort') === 'oldest')>Terlama</option><option value="a-z" @selected(request('sort') === 'a-z')>A–Z</option><option value="z-a" @selected(request('sort') === 'z-a')>Z–A</option></select></div>
            <div class="flex gap-2"><button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Cari</button><a href="{{ route('companies.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50">Reset</a></div>
        </form>
        <section data-public-list="companies" aria-live="polite">
            <p data-list-status class="mb-4 text-sm text-slate-600" role="status">Memuat perusahaan…</p>
            <div data-list-items class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"></div>
            <nav data-list-pages class="mt-7 flex flex-wrap items-center gap-2" aria-label="Halaman perusahaan"></nav>
        </section>
    </div>
    <template id="company-list-card"><a class="group block min-w-0 rounded-xl border border-slate-200 bg-white p-6 hover:border-blue-300 hover:bg-blue-50/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><div class="flex items-center gap-4"><div class="relative grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-lg border border-slate-200 bg-slate-100"><span data-field="logo-fallback" class="font-bold text-blue-700" aria-hidden="true"></span><img data-field="logo" class="absolute inset-0 hidden h-full w-full bg-white object-contain" alt=""></div><div class="min-w-0"><h2 data-field="name" class="text-lg font-semibold text-slate-900 group-hover:text-blue-700"></h2><p data-field="location" class="mt-1 text-sm text-slate-600"></p></div></div><p data-field="description" class="mt-4 line-clamp-3 text-sm leading-6 text-slate-600"></p><span class="mt-5 inline-block text-sm font-semibold text-blue-700">Lihat profil →</span></a></template>
@endsection
