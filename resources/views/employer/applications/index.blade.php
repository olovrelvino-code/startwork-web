@extends('layouts.employer')

@section('title', 'Pelamar & CV')
@section('section_label', 'Pelamar & CV')

@section('content')
    <x-admin.page-header title="Pelamar & CV" :description="$applications->total().' lamaran untuk lowongan Anda.'" />

    <form method="GET" action="{{ route('employer.applications.index') }}" class="mb-5 flex flex-wrap items-end gap-3">
        @if (request('job_id'))<input type="hidden" name="job_id" value="{{ request('job_id') }}">@endif
        <div class="min-w-[220px] flex-1 sm:max-w-sm"><label class="admin-label" for="applicant-search">Cari pelamar</label><div class="relative"><input class="admin-input w-full pr-12" id="applicant-search" name="q" type="search" value="{{ request('q') }}" placeholder="Nama, lowongan, atau status"><button class="employer-search-button" type="submit" aria-label="Cari pelamar" title="Cari pelamar"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.2 4.2"/></svg></button></div></div>
        @if (request('q') || request('job_id'))<a class="admin-button-quiet" href="{{ route('employer.applications.index') }}">Hapus pencarian</a>@endif
    </form>

    <x-admin.data-table>
        <div class="md:hidden">
            @forelse ($applications as $application)
                <a class="block border-b border-slate-100 px-5 py-4 last:border-0" href="{{ route('employer.applications.show', $application->id) }}"><span class="flex items-start justify-between gap-3"><span class="font-semibold text-slate-900">{{ $application->full_name }}</span><x-admin.status-badge :status="$application->status" /></span><span class="mt-2 block text-sm text-slate-600">{{ $application->job_title }} · {{ $application->applied_at ? \Illuminate\Support\Carbon::parse($application->applied_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y') : 'Tanggal tidak tersedia' }}</span></a>
            @empty
                <x-admin.empty-state :title="request('q') || request('job_id') ? 'Tidak ada pelamar yang cocok' : 'Belum ada lamaran'" :description="request('q') || request('job_id') ? 'Coba kata kunci lain atau hapus pencarian.' : 'Lamaran akan muncul setelah ada lowongan aktif yang dilamar.'" />
            @endforelse
        </div>
        <table class="admin-table hidden md:table"><thead><tr><th>Pelamar</th><th>Lowongan</th><th>Status</th><th>Melamar</th></tr></thead><tbody>
            @forelse ($applications as $application)
                <tr><td><a class="admin-link" href="{{ route('employer.applications.show', $application->id) }}">{{ $application->full_name }}</a></td><td>{{ $application->job_title }}</td><td><x-admin.status-badge :status="$application->status" /></td><td>{{ $application->applied_at ? \Illuminate\Support\Carbon::parse($application->applied_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') : '—' }}</td></tr>
            @empty
                <tr><td colspan="4"><x-admin.empty-state :title="request('q') || request('job_id') ? 'Tidak ada pelamar yang cocok' : 'Belum ada lamaran'" :description="request('q') || request('job_id') ? 'Coba kata kunci lain atau hapus pencarian.' : 'Lamaran untuk lowongan Anda akan tampil di sini.'" /></td></tr>
            @endforelse
        </tbody></table>
    </x-admin.data-table>
    <x-admin.pagination :paginator="$applications" :numbered="true" />
@endsection
