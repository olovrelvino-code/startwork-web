@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('section_label', 'Dashboard')

@section('content')
    <x-admin.page-header title="Operasional hari ini" description="Pantau aktivitas platform dan masuk langsung ke pekerjaan yang memerlukan perhatian.">
        @if ($ownedCompanies > 0)
            <x-slot:actions><a href="{{ route('admin.job-posts.create') }}" class="admin-button-primary">Buat lowongan</a></x-slot:actions>
        @endif
    </x-admin.page-header>

    <section aria-label="Ringkasan platform" class="admin-panel mb-7 grid grid-cols-2 divide-x divide-y divide-slate-100 sm:grid-cols-4 sm:divide-y-0">
        @foreach ([
            ['Perusahaan', $counts['companies'], route('admin.companies.index')],
            ['Lowongan aktif', $counts['active_jobs'], route('admin.job-posts.index', ['status' => 'active'])],
            ['Total lamaran', $counts['applications'], route('admin.applicants.index')],
            ['Draf artikel', $counts['draft_articles'], route('admin.articles.index', ['status' => 'draft'])],
        ] as [$label, $value, $href])
            <a href="{{ $href }}" class="block p-5 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#B85B12]">
                <span class="block text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $label }}</span>
                <strong class="admin-number mt-3 block text-3xl font-semibold tracking-tight text-slate-900">{{ number_format($value, 0, ',', '.') }}</strong>
            </a>
        @endforeach
    </section>

    <div class="grid gap-7 xl:grid-cols-[minmax(0,1.5fr)_minmax(300px,1fr)]">
        <section aria-labelledby="recent-applicants-title">
            <div class="mb-3 flex items-baseline justify-between gap-4">
                <h2 id="recent-applicants-title" class="text-lg font-semibold text-slate-900">Lamaran terbaru</h2>
                <a href="{{ route('admin.applicants.index') }}" class="admin-link text-sm">Lihat semua</a>
            </div>
            <div class="admin-panel divide-y divide-slate-100">
                @forelse ($recentApplications as $application)
                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $application->full_name }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $application->job_title }} · {{ $application->company_name }}</p>
                        </div>
                        <x-admin.status-badge :status="$application->status" />
                    </div>
                @empty
                    <x-admin.empty-state title="Belum ada lamaran" description="Lamaran baru akan muncul di sini setelah pencari kerja mendaftar ke lowongan internal." />
                @endforelse
            </div>
        </section>

        <section aria-labelledby="recent-jobs-title">
            <div class="mb-3 flex items-baseline justify-between gap-4">
                <h2 id="recent-jobs-title" class="text-lg font-semibold text-slate-900">Lowongan terbaru</h2>
                <a href="{{ route('admin.job-posts.index') }}" class="admin-link text-sm">Lihat semua</a>
            </div>
            <div class="admin-panel divide-y divide-slate-100">
                @forelse ($recentJobs as $job)
                    <a href="{{ route('admin.job-posts.show', $job->id) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $job->title }}</span>
                            <span class="mt-1 block truncate text-xs text-slate-500">{{ $job->company_name }}</span>
                        </span>
                        <x-admin.status-badge :status="$job->status" />
                    </a>
                @empty
                        <x-admin.empty-state title="Belum ada lowongan" description="Lowongan yang dibuat untuk perusahaan milik admin akan tampil di sini setelah diterbitkan." />
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
