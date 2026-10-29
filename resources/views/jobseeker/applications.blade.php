@extends('layouts.public')

@section('title', 'Lamaran Saya')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8" data-my-applications>
        <header class="mb-8"><h1 class="text-3xl font-bold tracking-tight">Lamaran Saya</h1><p class="mt-2 text-slate-600">Pantau status lamaran yang Anda kirim melalui StartWork.</p></header>
        <p data-applications-status role="status" class="mb-4 text-sm text-slate-600">Memuat lamaran…</p>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white"><table class="min-w-[620px] w-full text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th scope="col" class="px-5 py-3 font-semibold">Posisi</th><th scope="col" class="px-5 py-3 font-semibold">Perusahaan</th><th scope="col" class="px-5 py-3 font-semibold">Melamar</th><th scope="col" class="px-5 py-3 font-semibold">Status</th></tr></thead><tbody data-applications-rows class="divide-y divide-slate-100"></tbody></table></div>
        <a href="{{ route('jobs.index') }}" class="mt-6 inline-block text-sm font-semibold text-blue-700 hover:underline">Cari lowongan lain →</a>
    </div>
@endsection
