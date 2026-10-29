@extends('layouts.employer')

@section('title', 'Dashboard Employer')
@section('section_label', 'Dashboard')

@section('content')
    <x-admin.page-header title="Rekrutmen" :description="$company->name">
        <x-slot:actions><a href="{{ route('employer.job-posts.create') }}" class="admin-button-primary">Buat lowongan</a></x-slot:actions>
    </x-admin.page-header>

    @if ($trial)
        @php($trialIsActive = $trial->status === 'active' && \Illuminate\Support\Carbon::parse($trial->starts_at)->lessThanOrEqualTo(now()) && \Illuminate\Support\Carbon::parse($trial->expires_at)->isFuture())
        <section class="mb-9 grid gap-3 border-l-[3px] border-[#B85B12] bg-slate-50 px-5 py-4 text-sm sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center" aria-label="Akses trial">
            <div>
                <h2 class="mb-1 text-xs font-semibold uppercase tracking-[0.12em] text-[#8F460F]">Akses trial</h2>
                @if ($trialIsActive)
                    <p class="leading-relaxed text-slate-700"><strong class="text-slate-900">Trial aktif.</strong> Terbitkan draft hingga {{ \Illuminate\Support\Carbon::parse($trial->expires_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB, sesuai kuota paket.</p>
                @else
                    <p class="leading-relaxed text-slate-700"><strong class="text-slate-900">Trial telah berakhir.</strong> Draft dan riwayat lowongan tetap tersedia; publikasi baru memerlukan akses aktif.</p>
                @endif
            </div>
            @if ($trialIsActive)
                <a class="admin-link whitespace-nowrap text-sm" href="{{ route('employer.job-posts.index') }}">Lihat draft lowongan</a>
            @endif
        </section>
    @endif

    <section aria-label="Ringkasan rekrutmen" class="mb-10">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Ringkasan rekrutmen</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4">
            @foreach ([
            ['Lamaran', $counts['applications'], route('employer.applications.index')],
            ['Lowongan', $counts['jobs'], route('employer.job-posts.index')],
            ['Aktif', $counts['active_jobs'], route('employer.job-posts.index')],
            ] as [$label, $value, $href])
                <a href="{{ $href }}" @class(['block min-w-0 rounded-xl border px-5 py-5 shadow-sm transition-[border-color,background-color,box-shadow] hover:border-[#B85B12]/60 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#2B506B]', 'col-span-2 border-[#E8D4C2] bg-[#FBF4EE] hover:bg-[#F8EEE5] sm:col-span-1' => $loop->first, 'border-slate-200 bg-[#F4F7F9] hover:bg-[#EDF3F7]' => ! $loop->first])>
                    <span class="block text-sm font-medium text-slate-600">{{ $label }}</span>
                    <strong @class(['admin-number mt-3 block font-semibold leading-none tracking-tight', 'text-3xl text-[#8F460F]' => $loop->first, 'text-2xl text-slate-900 sm:text-3xl' => ! $loop->first])>{{ number_format($value, 0, ',', '.') }}</strong>
                </a>
            @endforeach
        </div>
    </section>

    <form method="GET" action="{{ route('employer.dashboard') }}" role="search" class="mb-5 w-full max-w-md">
        <label class="admin-label" for="employer-dashboard-search">Cari data rekrutmen</label>
        <div class="flex items-center gap-2">
            <input class="admin-input min-w-0 flex-1" id="employer-dashboard-search" name="q" type="search" value="{{ $search }}" maxlength="255" placeholder="Cari nama pelamar atau judul lowongan...">
            <button type="submit" class="admin-button-secondary">Cari</button>
        </div>
        @if ($search !== '')<a href="{{ route('employer.dashboard') }}" class="admin-link mt-2 inline-block text-sm">Hapus pencarian</a>@endif
    </form>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.25fr)_minmax(340px,1fr)]">
        <section class="admin-panel min-w-0 p-5 shadow-sm sm:p-6" aria-labelledby="recent-applications-title">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 id="recent-applications-title" class="text-xl font-semibold tracking-tight text-slate-900">Lamaran terbaru</h2>
                <a class="admin-link shrink-0 text-sm" href="{{ route('employer.applications.index') }}">Lihat semua</a>
            </div>
            @if ($applications->isEmpty())
                <x-admin.empty-state :title="$filterApplications ? 'Lamaran tidak ditemukan' : 'Belum ada lamaran'" :description="$filterApplications ? 'Coba nama lain atau hapus pencarian.' : 'Lamaran akan muncul setelah ada lowongan aktif yang dilamar.'" />
            @else
                <div class="overflow-x-auto">
                    <table class="admin-table table-fixed [&_td]:px-2 [&_td]:py-3 [&_th]:px-2">
                        <thead><tr><th scope="col">Pelamar / lowongan</th><th scope="col" class="w-28 whitespace-nowrap text-right">Status</th></tr></thead>
                        <tbody>
                            @foreach ($applications as $application)
                                <tr>
                                    <td class="break-words"><span class="admin-number mr-2 text-xs text-slate-500">{{ $applications->firstItem() + $loop->index }}.</span><a class="admin-link" href="{{ route('employer.applications.show', $application->id) }}">{{ $application->full_name }}</a><span class="mt-1 block text-xs text-slate-500">{{ $application->job_title }}</span></td>
                                    <td class="whitespace-nowrap text-right"><x-admin.status-badge :status="$application->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <x-admin.pagination :paginator="$applications" :always="true" :numbered="true" />
        </section>

        <section class="admin-panel min-w-0 p-5 shadow-sm sm:p-6" aria-labelledby="recent-jobs-title">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 id="recent-jobs-title" class="text-lg font-semibold tracking-tight text-slate-900">Lowongan terbaru</h2>
                <a class="admin-link shrink-0 text-sm" href="{{ route('employer.job-posts.index') }}">Lihat semua</a>
            </div>
            @if ($jobs->isEmpty())
                <x-admin.empty-state :title="$filterJobs ? 'Lowongan tidak ditemukan' : 'Belum ada lowongan'" :description="$filterJobs ? 'Coba judul lain atau hapus pencarian.' : 'Draft lowongan yang Anda buat akan tampil di sini.'" />
            @else
                <div class="overflow-x-auto">
                    <table class="admin-table table-fixed [&_td]:px-2 [&_td]:py-3 [&_th]:px-2">
                        <thead><tr><th scope="col">Posisi</th><th scope="col" class="w-28 whitespace-nowrap text-right">Status</th></tr></thead>
                        <tbody>
                            @foreach ($jobs as $job)
                                <tr><td class="break-words"><span class="admin-number mr-2 text-xs text-slate-500">{{ $jobs->firstItem() + $loop->index }}.</span><a class="admin-link" href="{{ route('employer.job-posts.show', $job->id) }}">{{ $job->title }}</a></td><td class="whitespace-nowrap text-right"><x-admin.status-badge :status="$job->status" /></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <x-admin.pagination :paginator="$jobs" :always="true" :numbered="true" />
        </section>
    </div>
@endsection
