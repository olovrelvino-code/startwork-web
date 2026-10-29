@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('section_label', 'Dashboard')

@section('content')
    <x-admin.page-header title="Operasional hari ini" description="Pantau aktivitas platform dan masuk langsung ke pekerjaan yang memerlukan perhatian.">
        @if ($ownedCompanies > 0)
            <x-slot:actions><a href="{{ route('admin.job-posts.create') }}" class="admin-button-primary">Buat lowongan</a></x-slot:actions>
        @endif
    </x-admin.page-header>

    @if ($newCompaniesWithoutTrial > 0)
        <a href="{{ route('admin.companies.index', ['filter' => 'new']) }}" class="group mb-8 flex flex-wrap items-center justify-between gap-3 border-l-[3px] border-[#B85B12] bg-slate-50 px-5 py-4 text-sm text-slate-800 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#2B506B]">
            <span><strong class="text-slate-900">{{ $newCompaniesWithoutTrial }} perusahaan baru</strong> belum menerima trial</span><span class="font-semibold text-[#8F460F] group-hover:underline">Tinjau perusahaan →</span>
        </a>
    @endif

    <section aria-label="Ringkasan platform" class="mb-10">
        <h2 class="mb-3 text-sm font-semibold text-slate-600">Ringkasan platform</h2>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            @foreach ([
                ['Perusahaan', $counts['companies'], route('admin.companies.index'), 'M4 21V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v16M4 21h16M17 9h1a2 2 0 0 1 2 2v10M8 8h.01M12 8h.01M8 12h.01M12 12h.01'],
                ['Lowongan aktif', $counts['active_jobs'], route('admin.job-posts.index', ['status' => 'active']), 'M3 8a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8ZM8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18M10 12v2h4v-2'],
                ['Total lamaran', $counts['applications'], route('admin.applicants.index'), 'M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8M18 8a3 3 0 0 1 0 6M22 20v-2a4 4 0 0 0-3-3.87'],
                ['Draf artikel', $counts['draft_articles'], route('admin.articles.index', ['status' => 'draft']), 'M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2M15 2v6h5M8 12h8M8 16h8'],
            ] as [$label, $value, $href, $icon])
                <a href="{{ $href }}" @class(['block min-w-0 rounded-xl border px-5 py-5 shadow-sm transition-[border-color,background-color,box-shadow] hover:border-[#B85B12]/50 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#2B506B]', 'border-[#E8D4C2] bg-[#FBF4EE] hover:bg-[#F8EEE5]' => $loop->first, 'border-slate-200 bg-[#F4F7F9] hover:bg-[#EDF3F7]' => ! $loop->first])>
                    <span class="flex items-start justify-between gap-3 text-sm font-medium text-slate-700">
                        <span>{{ $label }}</span>
                        <svg aria-hidden="true" class="h-5 w-5 shrink-0 text-[#496477]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}" /></svg>
                    </span>
                    <strong @class(['admin-number mt-4 block text-3xl font-semibold leading-none tracking-tight', 'text-[#8F460F]' => $loop->first, 'text-[#183047]' => ! $loop->first])>{{ number_format($value, 0, ',', '.') }}</strong>
                </a>
            @endforeach
        </div>
    </section>

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(340px,1fr)]">
        <section aria-labelledby="recent-applicants-title" class="admin-panel admin-data-surface min-w-0 p-5 sm:p-6">
            <div class="mb-3 flex items-baseline justify-between gap-4">
                <h2 id="recent-applicants-title" class="text-xl font-semibold tracking-tight text-slate-900">Lamaran terbaru</h2>
                <a href="{{ route('admin.applicants.index') }}" class="admin-link text-sm">Lihat semua</a>
            </div>
            <form method="GET" action="{{ route('admin.dashboard') }}" role="search" class="mb-5 flex flex-wrap items-end gap-2" x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false" x-bind:aria-busy="loading.toString()">
                @if ($jobsSearch !== '')<input type="hidden" name="jobs_q" value="{{ $jobsSearch }}">@endif
                <div class="min-w-0 flex-1"><label class="admin-label" for="dashboard-applicants-search">Cari pelamar atau lowongan</label><input id="dashboard-applicants-search" class="admin-input" type="search" name="applicants_q" value="{{ $applicantsSearch }}" maxlength="255" placeholder="Nama pelamar atau posisi"></div>
                <button type="submit" class="admin-button-secondary" x-text="loading ? 'Mencari…' : 'Cari'">Cari</button>
                @if ($applicantsSearch !== '')<a href="{{ route('admin.dashboard', $jobsSearch !== '' ? ['jobs_q' => $jobsSearch] : []) }}" class="admin-button-quiet">Hapus</a>@endif
            </form>
            <div class="hidden grid-cols-[1.75rem_minmax(0,1fr)_auto] gap-x-4 border-b border-slate-200 pb-2 text-xs font-medium text-slate-500 sm:grid">
                <span>No.</span><span>Pelamar / lowongan</span><span>Status</span>
            </div>
            <div class="divide-y divide-slate-100 border-b border-slate-200">
                @forelse ($recentApplications as $application)
                    <div class="grid grid-cols-[1.75rem_minmax(0,1fr)] items-center gap-x-4 gap-y-2 py-3 sm:grid-cols-[1.75rem_minmax(0,1fr)_auto]">
                        <span class="admin-number w-7 text-sm text-slate-500">{{ $loop->iteration }}.</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $application->full_name }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $application->job_title }} · {{ $application->company_name }}</p>
                        </div>
                        <span class="col-start-2 sm:col-start-3"><x-admin.status-badge :status="$application->status" /></span>
                    </div>
                @empty
                    @if ($applicantsSearch !== '')
                        <x-admin.empty-state title="Lamaran tidak ditemukan" description="Coba kata kunci lain atau hapus pencarian." />
                    @else
                        <x-admin.empty-state title="Belum ada lamaran" description="Lamaran baru akan muncul di sini setelah pencari kerja mendaftar ke lowongan internal." />
                    @endif
                @endforelse
            </div>
        </section>

        <section aria-labelledby="recent-jobs-title" class="admin-panel admin-data-surface min-w-0 p-5 sm:p-6">
            <div class="mb-3 flex items-baseline justify-between gap-4">
                <h2 id="recent-jobs-title" class="text-lg font-semibold text-slate-900">Lowongan terbaru</h2>
                <a href="{{ route('admin.job-posts.index') }}" class="admin-link text-sm">Lihat semua</a>
            </div>
            <form method="GET" action="{{ route('admin.dashboard') }}" role="search" class="mb-5 flex flex-wrap items-end gap-2" x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false" x-bind:aria-busy="loading.toString()">
                @if ($applicantsSearch !== '')<input type="hidden" name="applicants_q" value="{{ $applicantsSearch }}">@endif
                <div class="min-w-0 flex-1"><label class="admin-label" for="dashboard-jobs-search">Cari lowongan</label><input id="dashboard-jobs-search" class="admin-input" type="search" name="jobs_q" value="{{ $jobsSearch }}" maxlength="255" placeholder="Posisi atau perusahaan"></div>
                <button type="submit" class="admin-button-secondary" x-text="loading ? 'Mencari…' : 'Cari'">Cari</button>
                @if ($jobsSearch !== '')<a href="{{ route('admin.dashboard', $applicantsSearch !== '' ? ['applicants_q' => $applicantsSearch] : []) }}" class="admin-button-quiet">Hapus</a>@endif
            </form>
            <div class="hidden grid-cols-[1.75rem_minmax(0,1fr)_auto] gap-x-4 border-b border-slate-200 pb-2 text-xs font-medium text-slate-500 sm:grid">
                <span>No.</span><span>Posisi / perusahaan</span><span>Status</span>
            </div>
            <div class="divide-y divide-slate-100 border-b border-slate-200">
                @forelse ($recentJobs as $job)
                    <a href="{{ route('admin.job-posts.show', $job->id) }}" class="grid grid-cols-[1.75rem_minmax(0,1fr)] items-center gap-x-4 gap-y-2 py-3 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#2B506B] sm:grid-cols-[1.75rem_minmax(0,1fr)_auto]">
                        <span class="admin-number w-7 text-sm text-slate-500">{{ $loop->iteration }}.</span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $job->title }}</span>
                            <span class="mt-1 block truncate text-xs text-slate-500">{{ $job->company_name }}</span>
                        </span>
                        <span class="col-start-2 sm:col-start-3"><x-admin.status-badge :status="$job->status" /></span>
                    </a>
                @empty
                    @if ($jobsSearch !== '')
                        <x-admin.empty-state title="Lowongan tidak ditemukan" description="Coba kata kunci lain atau hapus pencarian." />
                    @else
                        <x-admin.empty-state title="Belum ada lowongan" description="Lowongan yang dibuat untuk perusahaan milik admin akan tampil di sini setelah diterbitkan." />
                    @endif
                @endforelse
            </div>
        </section>
    </div>

    @if ($ownedCompanies === 0)
        <section class="mt-7 grid gap-4 rounded-xl bg-slate-900 p-6 text-white sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center" aria-labelledby="start-admin-title">
            <div>
                <h2 id="start-admin-title" class="text-lg font-semibold">Mulai dari perusahaan</h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-300">Akun admin ini belum memiliki perusahaan. Buat profil perusahaan terlebih dahulu agar Anda dapat menerbitkan lowongan atas namanya.</p>
            </div>
            <a href="{{ route('admin.companies.index') }}" class="admin-button-primary">Kelola perusahaan</a>
        </section>
    @endif
@endsection
