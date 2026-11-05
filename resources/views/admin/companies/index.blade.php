@extends('layouts.admin')

@section('title', 'Perusahaan')
@section('section_label', 'Perusahaan')

@section('content')
    <div x-data="{ showCreate: false }">
        <x-admin.page-header title="Perusahaan" description="Pantau seluruh perusahaan. Anda hanya dapat mengubah perusahaan yang dibuat dengan akun admin ini.">
            <x-slot:actions><button type="button" class="admin-button-primary" @click="showCreate = !showCreate" :aria-expanded="showCreate.toString()">Tambah perusahaan</button></x-slot:actions>
        </x-admin.page-header>

        <section x-cloak x-show="showCreate" class="admin-panel mb-7 p-5 sm:p-6" aria-label="Form perusahaan baru">
            <h2 class="mb-5 text-lg font-semibold text-slate-900">Perusahaan baru milik admin</h2>
            @include('admin.companies.partials.form')
        </section>
    </div>

    <nav class="mb-6 flex gap-6 overflow-x-auto border-b border-slate-200" aria-label="Filter perusahaan">
        @foreach (['all' => 'Semua', 'new' => 'Company Baru', 'no_trial' => 'Belum Trial', 'trial_active' => 'Trial Aktif', 'trial_expired' => 'Trial Berakhir'] as $value => $label)
            <a href="{{ route('admin.companies.index', array_filter(['filter' => $value === 'all' ? null : $value, 'q' => request('q')])) }}" @class(['shrink-0 border-b-2 pb-3 text-sm font-semibold', 'border-[#DE7C23] text-slate-900' => request('filter', 'all') === $value, 'border-transparent text-slate-500' => request('filter', 'all') !== $value]) @if (request('filter', 'all') === $value) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <x-admin.filter-bar :action="route('admin.companies.index')">
        @if (request('filter'))<input type="hidden" name="filter" value="{{ request('filter') }}">@endif
        <div class="w-full sm:max-w-xs">
            <label class="admin-label" for="company-search">Cari nama perusahaan</label>
            <input class="admin-input" id="company-search" name="q" type="search" value="{{ request('q') }}" placeholder="Nama perusahaan">
        </div>
    </x-admin.filter-bar>

    <p class="mb-3 text-sm text-slate-500">{{ number_format($companies->total(), 0, ',', '.') }} perusahaan ditemukan</p>
    @if ($companies->isEmpty())
        <div class="admin-panel"><x-admin.empty-state title="Perusahaan tidak ditemukan" description="Coba kata pencarian lain, atau tambahkan perusahaan baru dari tombol di atas." /></div>
    @else
        <x-admin.data-table>
            <x-slot:mobile>
                @foreach ($companies as $company)
                    <div class="px-4 py-4">
                        <span class="admin-number mb-1 block text-xs text-slate-500">{{ $companies->firstItem() + $loop->index }}.</span>
                        <span class="flex flex-wrap items-center gap-2 font-semibold text-slate-900">{{ $company->name }} <x-admin.company-flags :created-at="$company->created_at" :trial-expires-at="$company->trial_expires_at" :owner-role="$company->owner_role" /></span>
                        <span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">{{ $company->location ?: 'Lokasi belum diisi' }} <x-admin.ownership-label :owner-id="$company->owner_user_id" :owner-role="$company->owner_role" /></span>
                        <span class="mt-1 block text-xs text-slate-500">Terdaftar {{ \Illuminate\Support\Carbon::parse($company->created_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
                        <div class="mt-3 flex items-center gap-2">
                            <a href="{{ route('admin.companies.show', $company->id) }}" class="admin-button-quiet" aria-label="Edit perusahaan {{ $company->name }}" title="Edit perusahaan {{ $company->name }}"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4 10.5-10.5Z"/></svg></a>
                            @if (($company->owner_user_id === auth()->id() || $company->owner_role === 'employer') && ! $company->job_count && ! $company->subscription_count)
                                <x-admin.confirm-dialog title="Hapus perusahaan?" :message="'Perusahaan '.$company->name.' akan dihapus permanen. Akun pemilik tetap ada.'" :endpoint="url('/api/admin/companies/'.$company->id)" :success-url="route('admin.companies.index')" :button-label="'Hapus perusahaan '.$company->name"><x-slot:trigger><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m5 4v7m4-7v7"/></svg></x-slot:trigger></x-admin.confirm-dialog>
                            @else
                                <button type="button" class="admin-button-quiet w-11 text-slate-400" disabled aria-label="Perusahaan {{ $company->name }} tidak dapat dihapus karena kepemilikan atau riwayat terkait" title="Tidak dapat dihapus: kepemilikan, lowongan, atau subscription terkait"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m5 4v7m4-7v7"/></svg></button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </x-slot:mobile>
            <table class="admin-table">
                <thead><tr><th>No.</th><th>Perusahaan</th><th>Terdaftar</th><th>Lokasi</th><th>Kepemilikan</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @foreach ($companies as $company)
                        <tr>
                            <td class="admin-number text-slate-500">{{ $companies->firstItem() + $loop->index }}</td>
                            <td><span class="font-semibold text-slate-900">{{ $company->name }}</span><span class="mt-1 block"><x-admin.company-flags :created-at="$company->created_at" :trial-expires-at="$company->trial_expires_at" :owner-role="$company->owner_role" /></span></td>
                            <td class="whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($company->created_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</td>
                            <td>{{ $company->location ?: '—' }}</td>
                            <td><x-admin.ownership-label :owner-id="$company->owner_user_id" :owner-role="$company->owner_role" /></td>
                            <td class="text-right"><div class="inline-flex items-center gap-1">
                                <a href="{{ route('admin.companies.show', $company->id) }}" class="admin-button-quiet w-11 text-slate-700 hover:text-[#21546F]" aria-label="Edit perusahaan {{ $company->name }}" title="Edit perusahaan {{ $company->name }}"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4 10.5-10.5Z"/></svg></a>
                                @if (($company->owner_user_id === auth()->id() || $company->owner_role === 'employer') && ! $company->job_count && ! $company->subscription_count)
                                    <x-admin.confirm-dialog title="Hapus perusahaan?" :message="'Perusahaan '.$company->name.' akan dihapus permanen. Akun pemilik tetap ada.'" :endpoint="url('/api/admin/companies/'.$company->id)" :success-url="route('admin.companies.index')" :button-label="'Hapus perusahaan '.$company->name"><x-slot:trigger><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m5 4v7m4-7v7"/></svg></x-slot:trigger></x-admin.confirm-dialog>
                                @else
                                    <button type="button" class="admin-button-quiet w-11 text-slate-400" disabled aria-label="Perusahaan {{ $company->name }} tidak dapat dihapus karena kepemilikan atau riwayat terkait" title="Tidak dapat dihapus: kepemilikan, lowongan, atau subscription terkait"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m5 4v7m4-7v7"/></svg></button>
                                @endif
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.data-table>
    @endif
    <x-admin.pagination :paginator="$companies" :always="true" :english="true" :round="true" />
@endsection
