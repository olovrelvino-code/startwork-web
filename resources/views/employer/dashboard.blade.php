@extends('layouts.employer')

@section('title', 'Dashboard Employer')
@section('section_label', 'Dashboard')

@section('content')
    <x-admin.page-header title="Rekrutmen" :description="$company->name">
        <x-slot:actions><a href="{{ route('employer.job-posts.create') }}" class="admin-button-primary">Buat lowongan</a></x-slot:actions>
    </x-admin.page-header>

    @if ($trial)
        <section class="mb-8 flex flex-wrap items-center justify-between gap-3 border-y border-slate-200 py-4 text-sm" aria-label="Akses trial">
            @if ($trial->status === 'active' && \Illuminate\Support\Carbon::parse($trial->starts_at)->lessThanOrEqualTo(now()) && \Illuminate\Support\Carbon::parse($trial->expires_at)->isFuture())
                <p class="text-slate-700"><strong class="text-slate-900">Trial aktif.</strong> Terbitkan draft hingga {{ \Illuminate\Support\Carbon::parse($trial->expires_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB, sesuai kuota paket.</p>
                <a class="admin-link" href="{{ route('employer.job-posts.index') }}">Lihat draft lowongan</a>
            @else
                <p class="text-slate-700"><strong class="text-slate-900">Trial telah berakhir.</strong> Draft dan riwayat lowongan tetap tersedia; publikasi baru memerlukan akses aktif.</p>
            @endif
        </section>
    @endif

    <section aria-label="Ringkasan rekrutmen" class="mb-10 grid grid-cols-1 divide-y divide-slate-200 border-y border-slate-200 sm:grid-cols-3 sm:divide-x sm:divide-y-0 sm:py-6">
        @foreach ([
            ['Lowongan', $counts['jobs'], route('employer.job-posts.index')],
            ['Aktif', $counts['active_jobs'], route('employer.job-posts.index')],
            ['Lamaran', $counts['applications'], route('employer.applications.index')],
        ] as [$label, $value, $href])
            <a href="{{ $href }}" class="flex min-w-0 items-center justify-between gap-3 rounded-md px-2 py-3 first:pl-0 last:pr-0 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#2B506B] sm:block sm:px-6 sm:py-1">
                <span class="block text-xs font-medium text-slate-600 sm:text-sm">{{ $label }}</span>
                <strong class="admin-number block text-2xl font-semibold leading-tight text-slate-900 sm:mt-1 sm:text-3xl">{{ number_format($value, 0, ',', '.') }}</strong>
            </a>
        @endforeach
    </section>

    <div class="grid gap-10 xl:grid-cols-2">
        <section class="min-w-0 border-t border-slate-200 pt-5" aria-labelledby="recent-jobs-title">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 id="recent-jobs-title" class="text-lg font-semibold tracking-tight text-slate-900">Lowongan terbaru</h2>
                <a class="admin-link text-sm" href="{{ route('employer.job-posts.index') }}">Lihat semua</a>
            </div>
            @if ($jobs->isEmpty())
                <x-admin.empty-state title="Belum ada lowongan" description="Draft lowongan yang Anda buat akan tampil di sini." />
            @else
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead><tr><th scope="col">Posisi</th><th scope="col" class="whitespace-nowrap text-right">Status</th></tr></thead>
                        <tbody>
                            @foreach ($jobs as $job)
                                <tr><td><a class="admin-link" href="{{ route('employer.job-posts.show', $job->id) }}">{{ $job->title }}</a></td><td class="whitespace-nowrap text-right"><x-admin.status-badge :status="$job->status" /></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="min-w-0 border-t border-slate-200 pt-5" aria-labelledby="recent-applications-title">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 id="recent-applications-title" class="text-lg font-semibold tracking-tight text-slate-900">Lamaran terbaru</h2>
                <a class="admin-link text-sm" href="{{ route('employer.applications.index') }}">Lihat semua</a>
            </div>
            @if ($applications->isEmpty())
                <x-admin.empty-state title="Belum ada lamaran" description="Lamaran akan muncul setelah ada lowongan aktif yang dilamar." />
            @else
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead><tr><th scope="col">Pelamar / lowongan</th><th scope="col" class="whitespace-nowrap text-right">Status</th></tr></thead>
                        <tbody>
                            @foreach ($applications as $application)
                                <tr>
                                    <td><a class="admin-link" href="{{ route('employer.applications.show', $application->id) }}">{{ $application->full_name }}</a><span class="mt-1 block text-xs text-slate-500">{{ $application->job_title }}</span></td>
                                    <td class="whitespace-nowrap text-right"><x-admin.status-badge :status="$application->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
