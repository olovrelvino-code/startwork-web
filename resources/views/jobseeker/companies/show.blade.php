@extends('layouts.public')

@section('title', 'Profil perusahaan')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8" data-company-detail="{{ $id }}">
        <a href="{{ route('companies.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">← Semua perusahaan</a>
        <p data-detail-status role="status" class="mt-6 text-sm text-slate-600">Memuat profil perusahaan…</p>
        <div data-detail-content class="mt-6 hidden">
            <header class="flex flex-wrap items-center gap-5 rounded-xl border border-slate-200 bg-white p-6 sm:p-8"><img data-field="logo" class="hidden h-16 w-16 rounded-lg border border-slate-200 object-contain" alt=""><div><h1 data-field="name" class="text-3xl font-bold tracking-tight"></h1><p data-field="location" class="mt-2 text-slate-600"></p></div></header>
            <div class="mt-7 grid items-start gap-7 lg:grid-cols-[minmax(0,1fr)_280px]">
                <div class="space-y-7"><section class="rounded-xl border border-slate-200 bg-white p-6"><h2 class="text-xl font-semibold">Tentang perusahaan</h2><p data-field="description" class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700"></p></section><section><h2 class="text-xl font-semibold">Lowongan Terbuka</h2><div data-field="positions" class="mt-5 grid gap-4 sm:grid-cols-2"></div></section></div>
                <aside class="rounded-xl border border-slate-200 bg-white p-6 lg:sticky lg:top-5"><h2 class="text-lg font-semibold">Informasi perusahaan</h2><a data-field="website" class="mt-4 hidden break-all text-sm font-medium text-blue-700 hover:underline" target="_blank" rel="noopener noreferrer">Kunjungi website</a><a data-field="contact" class="mt-3 hidden break-all text-sm font-medium text-blue-700 hover:underline" target="_blank" rel="noopener noreferrer">Hubungi perusahaan</a></aside>
            </div>
        </div>
    </div>
    <template id="company-open-job-card">
        <a class="group block min-w-0 rounded-xl border border-slate-200 bg-white p-5 hover:border-blue-300 hover:bg-blue-50/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                    <span data-job-logo-fallback class="text-lg font-bold text-blue-700" aria-hidden="true"></span>
                    <img data-job-logo class="hidden h-full w-full object-contain p-1" alt="" loading="lazy">
                </div>
                <p data-job-company class="min-w-0 truncate text-sm font-semibold text-blue-700"></p>
            </div>
            <h3 data-job-title class="mt-4 text-lg font-semibold text-slate-900 group-hover:text-blue-700"></h3>
            <p data-job-location class="mt-2 text-sm text-slate-600"></p>
            <p data-job-types class="mt-2 text-xs text-slate-500"></p>
        </a>
    </template>
@endsection
