@extends('layouts.admin')

@section('title', 'Pelamar & CV')
@section('section_label', 'Pelamar & CV')

@section('content')
    <x-admin.page-header title="Pelamar & CV" description="Pantau lamaran dari semua perusahaan. Berkas CV hanya dapat diunduh lewat akses admin yang terlindungi." />

    <x-admin.filter-bar :action="route('admin.applicants.index')">
        <div class="w-full sm:max-w-md"><label class="admin-label" for="applicant-search">Cari pelamar</label><input class="admin-input" id="applicant-search" name="q" type="search" value="{{ request('q') }}" placeholder="Nama, lowongan, perusahaan, atau status"></div>
        @if (request('q'))<a class="admin-button-quiet" href="{{ route('admin.applicants.index') }}">Hapus pencarian</a>@endif
    </x-admin.filter-bar>

    <p class="mb-3 text-sm text-slate-500">{{ number_format($applicants->total(), 0, ',', '.') }} lamaran ditemukan</p>
    @if ($applicants->isEmpty())
        <div class="admin-panel"><x-admin.empty-state title="Belum ada pelamar yang cocok" description="Coba kata kunci lain atau hapus pencarian." /></div>
    @else
        <x-admin.data-table>
            <x-slot:mobile>
                @foreach ($applicants as $applicant)
                    <div class="flex items-start justify-between gap-3 px-4 py-4">
                        <span class="admin-number w-7 shrink-0 text-xs text-slate-500">{{ $applicants->firstItem() + $loop->index }}.</span>
                        <div class="min-w-0"><p class="font-semibold text-slate-900">{{ $applicant->full_name }}</p><p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $applicant->job_title }} · {{ $applicant->company_name }}</p><div class="mt-2"><x-admin.status-badge :status="$applicant->status" /></div></div>
                        @if ($applicant->cv_file)<a class="admin-link inline-flex shrink-0 cursor-pointer items-center gap-1.5 text-sm text-[#183047] hover:text-[#8F460F]" href="{{ url('/api/admin/applicants/'.$applicant->id.'/cv') }}" aria-label="Unduh CV {{ $applicant->full_name }}" title="Unduh CV {{ $applicant->full_name }}"><svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8l-5-5h-4M16 3v5h5M12 11v7m-3-3 3 3 3-3" /></svg>CV</a>@endif
                    </div>
                @endforeach
            </x-slot:mobile>
            <table class="admin-table">
                <thead><tr><th>No.</th><th>Pelamar</th><th>Posisi</th><th>Perusahaan</th><th>Status</th><th>Tanggal</th><th class="text-right">CV</th></tr></thead>
                <tbody>
                    @foreach ($applicants as $applicant)
                        <tr>
                            <td class="admin-number text-slate-500">{{ $applicants->firstItem() + $loop->index }}</td>
                            <td class="font-semibold text-slate-900">{{ $applicant->full_name }}</td>
                            <td>{{ $applicant->job_title }}</td>
                            <td>{{ $applicant->company_name }}</td>
                            <td><x-admin.status-badge :status="$applicant->status" /></td>
                            <td class="admin-number whitespace-nowrap">{{ $applicant->applied_at ? \Illuminate\Support\Carbon::parse($applicant->applied_at)->format('d M Y') : '—' }}</td>
                            <td class="text-right">@if ($applicant->cv_file)<a class="admin-link inline-flex cursor-pointer items-center gap-1.5 text-[#183047] hover:text-[#8F460F]" href="{{ url('/api/admin/applicants/'.$applicant->id.'/cv') }}" aria-label="Unduh CV {{ $applicant->full_name }}" title="Unduh CV {{ $applicant->full_name }}"><svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8l-5-5h-4M16 3v5h5M12 11v7m-3-3 3 3 3-3" /></svg>Unduh CV</a>@else<span class="text-slate-400">—</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.data-table>
    @endif
    <x-admin.pagination :paginator="$applicants" :always="true" :english="true" :round="true" />
@endsection
