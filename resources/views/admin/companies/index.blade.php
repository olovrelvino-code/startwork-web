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

    <nav class="mb-5 flex flex-wrap gap-2" aria-label="Filter perusahaan">
        @foreach (['all' => 'Semua', 'new' => 'Company Baru', 'no_trial' => 'Belum Trial', 'trial_active' => 'Trial Aktif', 'trial_expired' => 'Trial Berakhir'] as $value => $label)
            <a href="{{ route('admin.companies.index', array_filter(['filter' => $value === 'all' ? null : $value, 'q' => request('q')])) }}" @class(['rounded-md border px-3 py-2 text-sm font-medium transition-colors', 'border-slate-900 bg-slate-900 text-white' => request('filter', 'all') === $value, 'border-slate-200 bg-white text-slate-700 hover:border-slate-400' => request('filter', 'all') !== $value]) @if (request('filter', 'all') === $value) aria-current="page" @endif>{{ $label }}</a>
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
                    <a href="{{ route('admin.companies.show', $company->id) }}" class="block px-4 py-4">
                        <span class="admin-number mb-1 block text-xs text-slate-500">{{ $companies->firstItem() + $loop->index }}.</span>
                        <span class="flex flex-wrap items-center gap-2 font-semibold text-slate-900">{{ $company->name }} <x-admin.company-flags :created-at="$company->created_at" :trial-expires-at="$company->trial_expires_at" :owner-role="$company->owner_role" /></span>
                        <span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">{{ $company->location ?: 'Lokasi belum diisi' }} <x-admin.ownership-label :owner-id="$company->owner_user_id" :owner-role="$company->owner_role" /></span>
                        <span class="mt-1 block text-xs text-slate-500">Terdaftar {{ \Illuminate\Support\Carbon::parse($company->created_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
                    </a>
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
                            <td class="text-right"><a href="{{ route('admin.companies.show', $company->id) }}" class="admin-link">Lihat detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-admin.data-table>
    @endif
    <x-admin.pagination :paginator="$companies" :always="true" :english="true" :round="true" />
@endsection
