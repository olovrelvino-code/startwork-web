@extends('layouts.admin')

@section('title', 'Pelamar & CV')
@section('section_label', 'Pelamar & CV')

@section('content')
    <x-admin.page-header title="Pelamar & CV" description="Pantau lamaran dari semua perusahaan. Berkas CV hanya dapat diunduh lewat akses admin yang terlindungi." />

    <x-admin.filter-bar :action="route('admin.applicants.index')">
        <div class="w-full sm:max-w-xs"><label class="admin-label" for="applicant-name">Nama pelamar</label><input class="admin-input" id="applicant-name" name="name" type="search" value="{{ request('name') }}" placeholder="Cari nama"></div>
        <div class="w-full sm:max-w-xs"><label class="admin-label" for="applicant-company">Perusahaan</label><select class="admin-input" id="applicant-company" name="company_id"><option value="">Semua perusahaan</option>@foreach ($companies as $company)<option value="{{ $company->id }}" @selected(request('company_id') === $company->id)>{{ $company->name }}</option>@endforeach</select></div>
    </x-admin.filter-bar>

    <p class="mb-3 text-sm text-slate-500">{{ number_format($applicants->total(), 0, ',', '.') }} lamaran ditemukan</p>
    @if ($applicants->isEmpty())
        <div class="admin-panel"><x-admin.empty-state title="Belum ada pelamar yang cocok" description="Ubah filter untuk melihat lamaran dari perusahaan lain." /></div>
    @else
        <x-admin.data-table>
            <x-slot:mobile>
                @foreach ($applicants as $applicant)
                    <div class="flex items-start justify-between gap-3 px-4 py-4">
                        <div class="min-w-0"><p class="font-semibold text-slate-900">{{ $applicant->full_name }}</p><p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $applicant->job_title }} · {{ $applicant->company_name }}</p><div class="mt-2"><x-admin.status-badge :status="$applicant->status" /></div></div>
                        @if ($applicant->cv_file)<a class="admin-link shrink-0 text-sm" href="{{ url('/api/admin/applicants/'.$applicant->id.'/cv') }}">CV</a>@endif
                    </div>
                @endforeach
            </x-slot:mobile>
            <table class="admin-table">
                <thead><tr><th>Pelamar</th><th>Posisi</th><th>Perusahaan</th><th>Status</th><th>Tanggal</th><th class="text-right">CV</th></tr></thead>
                <tbody>
                    @foreach ($applicants as $applicant)
                        <tr>
                            <td class="font-semibold text-slate-900">{{ $applicant->full_name }}</td>
                            <td>{{ $applicant->job_title }}</td>
                            <td>{{ $applicant->company_name }}</td>
                            <td><x-admin.status-badge :status="$applicant->status" /></td>
                            <td class="admin-number whitespace-nowrap">{{ $applicant->applied_at ? \Illuminate\Support\Carbon::parse($applicant->applied_at)->format('d M Y') : '—' }}</td>
                            <td class="text-right">@if ($applicant->cv_file)<a class="admin-link" href="{{ url('/api/admin/applicants/'.$applicant->id.'/cv') }}">Unduh CV</a>@else<span class="text-slate-400">—</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.data-table>
        <x-admin.pagination :paginator="$applicants" />
    @endif
@endsection
