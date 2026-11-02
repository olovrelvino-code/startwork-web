@extends('layouts.public')

@section('title', 'Lowongan tersimpan')
@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold">Lowongan tersimpan</h1>
        <p class="mt-2 text-slate-600">Lowongan yang Anda simpan untuk dilihat kembali.</p>
        @if ($jobs->isEmpty())
            <p class="mt-8 rounded-xl border border-slate-200 bg-white p-6 text-slate-600">Belum ada lowongan tersimpan. <a class="font-semibold text-blue-700 underline" href="{{ route('jobs.index') }}">Jelajahi lowongan</a></p>
        @else
            <div data-saved-grid class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($jobs as $job)
                    <article data-saved-card class="relative rounded-xl border border-slate-200 bg-white p-5">
                        <button type="button" data-save-job="{{ $job->id }}" aria-pressed="true" aria-label="Hapus {{ $job->title }} dari lowongan tersimpan" title="Hapus dari tersimpan" class="jobseeker-save-button absolute right-4 top-4 z-10 rounded-full p-2 focus-visible:outline focus-visible:outline-2"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M6 3.5h12v17l-6-4-6 4z"/></svg></button>
                        <a href="{{ route('jobs.show', $job->id) }}" class="block pr-10 focus-visible:outline focus-visible:outline-2">
                            <div class="flex items-center gap-3"><span class="relative grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-lg bg-slate-100 font-bold text-blue-700">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($job->company_name, 0, 1)) }}@if ($job->company_logo)<img src="{{ url('/api/companies/'.$job->company_id.'/logo') }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain p-1" @@error="$el.remove()">@endif</span><span class="text-sm font-semibold text-blue-700">{{ $job->company_name }}</span></div>
                            <h2 class="mt-4 text-lg font-semibold">{{ $job->title }}</h2>
                            <p class="mt-2 text-sm text-slate-600">{{ $job->location ?: 'Lokasi tidak dicantumkan' }}</p>
                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium leading-5">
                                @if ($job->category)<span class="jobseeker-job-chip" data-kind="category">{{ $job->category }}</span>@endif
                                @if ($job->job_type)<span class="jobseeker-job-chip" data-kind="job-type">{{ $job->job_type }}</span>@endif
                                @if ($job->work_type)<span class="jobseeker-job-chip" data-kind="work-type">{{ $job->work_type }}</span>@endif
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
            <p data-saved-empty class="mt-8 hidden rounded-xl border border-slate-200 bg-white p-6 text-slate-600">Belum ada lowongan tersimpan. <a class="font-semibold text-blue-700 underline" href="{{ route('jobs.index') }}">Jelajahi lowongan</a></p>
            <div data-saved-pagination class="mt-8">{{ $jobs->links() }}</div>
        @endif
        <p data-save-feedback role="status" class="mt-4 text-sm text-slate-600" aria-live="polite"></p>
    </section>
@endsection
