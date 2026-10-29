@extends('layouts.admin')

@section('title', 'Lowongan Kerja')
@section('section_label', 'Lowongan Kerja')

@section('content')
    <x-admin.page-header title="Lowongan kerja" description="Pantau seluruh lowongan. Kelola posting Employer dan posting milik akun admin ini.">
        <x-slot:actions><a href="{{ route('admin.job-posts.create') }}" class="admin-button-primary">Buat lowongan</a></x-slot:actions>
    </x-admin.page-header>

    <nav class="mb-6 flex gap-6 overflow-x-auto border-b border-slate-200" aria-label="Cakupan lowongan">
        <a href="{{ route('admin.job-posts.index', array_filter(['status' => request('status'), 'company' => request('company'), 'company_id' => request('company_id'), 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}" @class(['shrink-0 border-b-2 pb-3 text-sm font-semibold', 'border-[#EE8A34] text-slate-900' => ! request()->boolean('mine'), 'border-transparent text-slate-500' => request()->boolean('mine')])>Semua lowongan</a>
        <a href="{{ route('admin.job-posts.index', array_filter(['mine' => 1, 'status' => request('status'), 'company' => request('company'), 'company_id' => request('company_id'), 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}" @class(['shrink-0 border-b-2 pb-3 text-sm font-semibold', 'border-[#EE8A34] text-slate-900' => request()->boolean('mine'), 'border-transparent text-slate-500' => ! request()->boolean('mine')])>Posting saya</a>
    </nav>

    <x-admin.filter-bar :action="route('admin.job-posts.index')" class="flex-wrap">
        @if (request()->boolean('mine'))<input type="hidden" name="mine" value="1">@endif
        <div class="w-full sm:max-w-44"><label class="admin-label" for="job-status">Status</label><select class="admin-input" id="job-status" name="status"><option value="">Semua status</option>@foreach (['active' => 'Aktif', 'inactive' => 'Nonaktif', 'expired' => 'Kedaluwarsa'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="w-full sm:max-w-xs">
            <label class="admin-label" for="job-company-filter">Perusahaan</label>
            <input class="admin-input text-left" id="job-company-filter" name="company" type="search" value="{{ request('company', $selectedCompany?->name ?? '') }}" maxlength="255" placeholder="Cari nama perusahaan">
        </div>
        <div class="w-full sm:max-w-44"><label class="admin-label" for="job-date-from">Dibuat mulai</label><input class="admin-input" id="job-date-from" name="date_from" type="date" value="{{ request('date_from') }}"></div>
        <div class="w-full sm:max-w-44"><label class="admin-label" for="job-date-to">Dibuat sampai</label><input class="admin-input" id="job-date-to" name="date_to" type="date" min="{{ request('date_from') }}" value="{{ request('date_to') }}">@error('date_to')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
        @if (request('date_from') || request('date_to'))
            <a class="admin-button-quiet" href="{{ route('admin.job-posts.index', array_filter(['mine' => request()->boolean('mine') ? 1 : null, 'status' => request('status'), 'company' => request('company'), 'company_id' => request('company_id')])) }}">Reset tanggal</a>
        @endif
    </x-admin.filter-bar>

    <p class="mb-3 text-sm text-slate-500">{{ number_format($jobs->total(), 0, ',', '.') }} lowongan ditemukan</p>
    @if ($jobs->isEmpty())
        <div class="admin-panel"><x-admin.empty-state title="Tidak ada lowongan yang cocok" description="Coba ubah filter, atau buat lowongan untuk perusahaan milik Anda." :href="route('admin.job-posts.create')" action="Buat lowongan" /></div>
    @else
        <x-admin.data-table>
            <x-slot:mobile>
                @foreach ($jobs as $job)
                    <a href="{{ route('admin.job-posts.show', $job->id) }}" class="block px-4 py-4 text-left"><span class="admin-number mb-1 block text-xs text-slate-500">{{ $jobs->firstItem() + $loop->index }}.</span><span class="block font-semibold text-slate-900">{{ $job->title }}</span><span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">{{ $job->company_name }} <x-admin.status-badge :status="$job->status" /></span></a>
                @endforeach
            </x-slot:mobile>
            <table class="admin-table">
                <thead><tr><th>No.</th><th>Posisi</th><th>Perusahaan</th><th>Status</th><th>Alur lamaran</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @foreach ($jobs as $job)
                        <tr>
                            <td class="admin-number text-slate-500">{{ $jobs->firstItem() + $loop->index }}</td>
                            <td class="max-w-xs font-semibold text-slate-900">{{ $job->title }}</td>
                            <td class="text-left">{{ $job->company_name }}</td>
                            <td><x-admin.status-badge :status="$job->status" /></td>
                            <td>{{ $job->apply_url ? 'Eksternal' : 'Internal' }}</td>
                            <td class="text-right"><a href="{{ route('admin.job-posts.show', $job->id) }}" class="admin-link">Lihat detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.data-table>
    @endif
    <x-admin.pagination :paginator="$jobs" :always="true" :english="true" :round="true" />
@endsection
