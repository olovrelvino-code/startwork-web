@extends('layouts.employer')

@section('title', 'Lowongan kerja')
@section('section_label', 'Lowongan kerja')

@section('content')
    <x-admin.page-header title="Lowongan kerja" :description="$company ? $company->name.' · '.$jobs->total().' lowongan' : 'Buat profil perusahaan untuk mulai menyiapkan lowongan.'">
        <x-slot:actions><a class="admin-button-primary" href="{{ $company ? route('employer.job-posts.create') : route('employer.setup') }}">{{ $company ? 'Buat lowongan' : 'Buat perusahaan' }}</a></x-slot:actions>
    </x-admin.page-header>

    <form method="GET" action="{{ route('employer.job-posts.index') }}" role="search" class="mb-5 flex flex-wrap items-end gap-3">
        <div class="min-w-0 flex-1 basis-full sm:basis-64 sm:grow-0"><label class="admin-label" for="employer-job-search">Cari lowongan</label><input class="admin-input" id="employer-job-search" name="q" type="search" value="{{ $search }}" maxlength="255" placeholder="Judul atau lokasi"></div>
        <button type="submit" class="admin-button-secondary">Cari</button>
        @if ($search !== '')<a href="{{ route('employer.job-posts.index') }}" class="admin-button-quiet">Hapus pencarian</a>@endif
    </form>

    <x-admin.data-table>
        <div class="md:hidden">
            @forelse ($jobs as $job)
                <a href="{{ route('employer.job-posts.show', $job->id) }}" class="block border-b border-slate-100 px-5 py-4 last:border-0"><span class="flex items-start justify-between gap-3"><span class="font-semibold text-slate-900">{{ $job->title }}</span><x-admin.status-badge :status="$job->status" /></span><span class="mt-2 block text-sm text-slate-600">{{ $job->location }} · {{ \Illuminate\Support\Carbon::parse($job->created_at)->setTimezone('Asia/Jakarta')->format('d M Y') }}</span></a>
            @empty
                <x-admin.empty-state :title="$search !== '' ? 'Lowongan tidak ditemukan' : 'Belum ada lowongan'" :description="$search !== '' ? 'Coba judul atau lokasi lain, atau hapus pencarian.' : 'Lowongan yang Anda buat akan tersimpan sebagai draft inactive.'" />
            @endforelse
        </div>
        <table class="admin-table hidden md:table"><thead><tr><th>Lowongan</th><th>Lokasi</th><th>Status</th><th>Dibuat</th></tr></thead><tbody>
            @forelse ($jobs as $job)
                <tr><td><a class="admin-link" href="{{ route('employer.job-posts.show', $job->id) }}">{{ $job->title }}</a></td><td>{{ $job->location }}</td><td><x-admin.status-badge :status="$job->status" /></td><td>{{ \Illuminate\Support\Carbon::parse($job->created_at)->setTimezone('Asia/Jakarta')->format('d M Y') }}</td></tr>
            @empty
                <tr><td colspan="4"><x-admin.empty-state :title="$search !== '' ? 'Lowongan tidak ditemukan' : 'Belum ada lowongan'" :description="$search !== '' ? 'Coba judul atau lokasi lain, atau hapus pencarian.' : 'Buat lowongan pertama untuk menyiapkan draft.'" /></td></tr>
            @endforelse
        </tbody></table>
    </x-admin.data-table>
    <x-admin.pagination :paginator="$jobs" :numbered="true" />
@endsection
