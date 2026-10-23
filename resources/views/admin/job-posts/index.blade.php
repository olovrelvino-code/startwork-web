@extends('layouts.admin')

@section('title', 'Lowongan Kerja')
@section('section_label', 'Lowongan Kerja')

@section('content')
    <x-admin.page-header title="Lowongan kerja" description="Pantau seluruh lowongan. Pengubahan hanya tersedia untuk posting yang dibuat oleh akun admin ini pada perusahaannya sendiri.">
        <x-slot:actions><a href="{{ route('admin.job-posts.create') }}" class="admin-button-primary">Buat lowongan</a></x-slot:actions>
    </x-admin.page-header>

    <nav class="mb-6 flex gap-6 overflow-x-auto border-b border-slate-200" aria-label="Cakupan lowongan">
        <a href="{{ route('admin.job-posts.index', array_filter(['status' => request('status'), 'company_id' => request('company_id')])) }}" @class(['shrink-0 border-b-2 pb-3 text-sm font-semibold', 'border-[#EE8A34] text-slate-900' => ! request()->boolean('mine'), 'border-transparent text-slate-500' => request()->boolean('mine')])>Semua lowongan</a>
        <a href="{{ route('admin.job-posts.index', array_filter(['mine' => 1, 'status' => request('status'), 'company_id' => request('company_id')])) }}" @class(['shrink-0 border-b-2 pb-3 text-sm font-semibold', 'border-[#EE8A34] text-slate-900' => request()->boolean('mine'), 'border-transparent text-slate-500' => ! request()->boolean('mine')])>Posting saya</a>
    </nav>

    <x-admin.filter-bar :action="route('admin.job-posts.index')">
        @if (request()->boolean('mine'))<input type="hidden" name="mine" value="1">@endif
        <div class="w-full sm:max-w-44"><label class="admin-label" for="job-status">Status</label><select class="admin-input" id="job-status" name="status"><option value="">Semua status</option>@foreach (['active' => 'Aktif', 'inactive' => 'Nonaktif', 'expired' => 'Kedaluwarsa'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="w-full sm:max-w-xs"><label class="admin-label" for="job-company-filter">Perusahaan</label><select class="admin-input" id="job-company-filter" name="company_id"><option value="">Semua perusahaan</option>@foreach ($companies as $company)<option value="{{ $company->id }}" @selected(request('company_id') === $company->id)>{{ $company->name }}</option>@endforeach</select></div>
    </x-admin.filter-bar>

    <p class="mb-3 text-sm text-slate-500">{{ number_format($jobs->total(), 0, ',', '.') }} lowongan ditemukan</p>
    @if ($jobs->isEmpty())
        <div class="admin-panel"><x-admin.empty-state title="Tidak ada lowongan yang cocok" description="Coba ubah filter, atau buat lowongan untuk perusahaan milik Anda." :href="route('admin.job-posts.create')" action="Buat lowongan" /></div>
    @else
        <x-admin.data-table>
            <x-slot:mobile>
                @foreach ($jobs as $job)
                    <a href="{{ route('admin.job-posts.show', $job->id) }}" class="block px-4 py-4"><span class="block font-semibold text-slate-900">{{ $job->title }}</span><span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">{{ $job->company_name }} <x-admin.status-badge :status="$job->status" /></span></a>
                @endforeach
            </x-slot:mobile>
            <table class="admin-table">
                <thead><tr><th>Posisi</th><th>Perusahaan</th><th>Status</th><th>Alur lamaran</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @foreach ($jobs as $job)
                        <tr>
                            <td class="max-w-xs font-semibold text-slate-900">{{ $job->title }}</td>
                            <td>{{ $job->company_name }}</td>
                            <td><x-admin.status-badge :status="$job->status" /></td>
                            <td>{{ $job->apply_url ? 'Eksternal' : 'Internal' }}</td>
                            <td class="text-right"><a href="{{ route('admin.job-posts.show', $job->id) }}" class="admin-link">Lihat detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.data-table>
        <x-admin.pagination :paginator="$jobs" />
    @endif
@endsection
