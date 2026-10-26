@extends('layouts.employer')

@section('title', 'Dashboard Employer')
@section('section_label', 'Dashboard')

@section('content')
    <x-admin.page-header title="Rekrutmen" :description="$company->name">
        <x-slot:actions><a href="{{ route('employer.job-posts.create') }}" class="admin-button-primary">Buat lowongan</a></x-slot:actions>
    </x-admin.page-header>

    <section aria-label="Ringkasan rekrutmen" class="mb-10 grid grid-cols-3 divide-x divide-slate-200 border-y border-slate-200 py-5">
        @foreach ([
            ['Lowongan', $counts['jobs'], route('employer.job-posts.index')],
            ['Aktif', $counts['active_jobs'], route('employer.job-posts.index')],
            ['Lamaran', $counts['applications'], route('employer.applications.index')],
        ] as [$label, $value, $href])
            <a href="{{ $href }}" class="min-w-0 px-3 first:pl-0 last:pr-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#B85B12] sm:px-6">
                <span class="block text-xs text-slate-500 sm:text-sm">{{ $label }}</span>
                <strong class="admin-number mt-1 block text-2xl font-semibold text-slate-900 sm:text-3xl">{{ number_format($value, 0, ',', '.') }}</strong>
            </a>
        @endforeach
    </section>

    <div class="grid gap-10 xl:grid-cols-2">
        <section class="min-w-0 border-t border-slate-200 pt-5" aria-labelledby="recent-jobs-title">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 id="recent-jobs-title" class="text-base font-semibold text-slate-900">Lowongan terbaru</h2>
                <a class="admin-link text-sm" href="{{ route('employer.job-posts.index') }}">Lihat semua</a>
            </div>
            @if ($jobs->isEmpty())
                <x-admin.empty-state title="Belum ada lowongan" description="Draft lowongan yang Anda buat akan tampil di sini." />
            @else
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead><tr><th scope="col">Posisi</th><th scope="col" class="text-right">Status</th></tr></thead>
                        <tbody>
                            @foreach ($jobs as $job)
                                <tr><td><a class="admin-link" href="{{ route('employer.job-posts.show', $job->id) }}">{{ $job->title }}</a></td><td class="text-right"><x-admin.status-badge :status="$job->status" /></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="min-w-0 border-t border-slate-200 pt-5" aria-labelledby="recent-applications-title">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 id="recent-applications-title" class="text-base font-semibold text-slate-900">Lamaran terbaru</h2>
                <a class="admin-link text-sm" href="{{ route('employer.applications.index') }}">Lihat semua</a>
            </div>
            @if ($applications->isEmpty())
                <x-admin.empty-state title="Belum ada lamaran" description="Lamaran akan muncul setelah ada lowongan aktif yang dilamar." />
            @else
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead><tr><th scope="col">Pelamar / lowongan</th><th scope="col" class="text-right">Status</th></tr></thead>
                        <tbody>
                            @foreach ($applications as $application)
                                <tr>
                                    <td><a class="admin-link" href="{{ route('employer.applications.show', $application->id) }}">{{ $application->full_name }}</a><span class="mt-1 block text-xs text-slate-500">{{ $application->job_title }}</span></td>
                                    <td class="text-right"><x-admin.status-badge :status="$application->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
