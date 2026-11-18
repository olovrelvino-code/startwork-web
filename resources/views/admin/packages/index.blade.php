@extends('layouts.admin')

@section('title', 'Paket & Promo')
@section('section_label', 'Paket & Promo')

@section('content')
    <x-admin.page-header title="Paket & Promo" description="Katalog paket aktif sebagai acuan operasional perusahaan.">
        <x-slot:actions><button type="button" class="admin-button-primary" onclick="document.getElementById('create-package-dialog').showModal()"><svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg><span>Tambah Paket</span></button></x-slot:actions>
    </x-admin.page-header>
    <x-admin.notice type="info" message="Trial Basic memberi 10 lowongan selama 15 hari. Pembelian paket diproses melalui WhatsApp dan diverifikasi admin sebelum aktif." />
    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
    @endif
    <nav class="mb-5 flex gap-5 border-b border-slate-200" aria-label="Status paket">
        <a href="{{ route('admin.packages.index') }}" @class(['border-b-2 pb-3 text-sm font-semibold', 'border-[#B85B12] text-slate-900' => $status === 'active', 'border-transparent text-slate-500' => $status !== 'active'])>Aktif</a>
        <a href="{{ route('admin.packages.index', ['status' => 'draft']) }}" @class(['border-b-2 pb-3 text-sm font-semibold', 'border-[#B85B12] text-slate-900' => $status === 'draft', 'border-transparent text-slate-500' => $status !== 'draft'])>Draft / nonaktif</a>
    </nav>
    <section aria-label="Katalog paket" class="admin-panel admin-data-surface divide-y divide-slate-100">
        @forelse ($packages as $package)
            <div class="grid gap-4 px-5 py-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center lg:grid-cols-[minmax(0,1fr)_auto_auto] lg:gap-8">
                <div class="min-w-0"><span class="admin-number text-xs text-slate-500">{{ $packages->firstItem() + $loop->index }}.</span><h2 class="text-lg font-semibold text-slate-900">{{ $package->name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $package->quota }} lowongan · {{ $package->duration_days }} hari</p>@if ($package->benefit_list)<p class="mt-2 text-xs leading-relaxed text-slate-600">{{ implode(' · ', $package->benefit_list) }}</p>@endif</div>
                <p class="admin-number text-lg font-semibold text-slate-900">Rp {{ number_format($package->price, 0, ',', '.') }}</p>
                <div class="flex flex-wrap items-center gap-2 sm:col-span-2 lg:col-span-1">
                    <button type="button" class="admin-button-quiet inline-flex items-center gap-1.5 text-sm" onclick="document.getElementById('edit-package-{{ $package->id }}').showModal()" aria-label="Edit paket {{ $package->name }}"><svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4 10.5-10.5Z"/></svg>Edit</button>
                    <form method="POST" action="{{ route('admin.packages.toggle', $package->id) }}">@csrf @method('PATCH')<button type="submit" class="admin-button-secondary text-xs">{{ $package->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                    @if (! in_array($package->name, ['Basic', 'Standard', 'Premium'], true) && ! $package->has_subscriptions)
                        <form method="POST" action="{{ route('admin.packages.destroy', $package->id) }}" onsubmit="return confirm('Hapus paket ini? Paket dengan riwayat pembelian tidak dapat dihapus.')">@csrf @method('DELETE')<button type="submit" class="admin-button-quiet text-xs text-rose-700">Hapus</button></form>
                    @endif
                </div>
            </div>
        @empty
            <x-admin.empty-state :title="$status === 'active' ? 'Belum ada paket aktif' : 'Belum ada paket draft'" description="Katalog akan tampil setelah paket tersedia di sistem." />
        @endforelse
    </section>
    <x-admin.pagination :paginator="$packages" :always="true" :english="true" :round="true" />
    <dialog id="create-package-dialog" class="w-[min(94vw,42rem)] rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-xl backdrop:bg-slate-950/60 sm:p-8" aria-labelledby="create-package-title">
        <h2 id="create-package-title" class="mb-5 text-xl font-semibold">Tambah Paket / Promo</h2>
        @include('admin.packages.partials.form', ['package' => null])
    </dialog>
    @foreach ($packages as $package)
        <dialog id="edit-package-{{ $package->id }}" class="w-[min(94vw,42rem)] rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-xl backdrop:bg-slate-950/60 sm:p-8" aria-labelledby="edit-package-title-{{ $package->id }}">
            <h2 id="edit-package-title-{{ $package->id }}" class="mb-5 text-xl font-semibold">Edit {{ $package->name }}</h2>
            @include('admin.packages.partials.form', ['package' => $package])
        </dialog>
    @endforeach
    @if ($errors->any())
        <script>document.addEventListener('DOMContentLoaded', () => document.getElementById(@json(old('_package_form') === 'create' ? 'create-package-dialog' : 'edit-package-'.old('_package_form')))?.showModal());</script>
    @endif
@endsection
