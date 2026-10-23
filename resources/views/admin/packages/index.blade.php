@extends('layouts.admin')

@section('title', 'Paket & Promo')
@section('section_label', 'Paket & Promo')

@section('content')
    <x-admin.page-header title="Paket & Promo" description="Katalog paket aktif sebagai acuan operasional perusahaan." />
    <x-admin.notice type="warning" message="Pemberian trial dan pembayaran masih ditunda. Endpoint grant-trial mengembalikan 501; tidak ada aksi aktivasi pada halaman ini." />
    <section aria-label="Katalog paket" class="admin-panel divide-y divide-slate-100">
        @forelse ($packages as $package)
            <div class="grid gap-3 px-5 py-5 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:gap-8">
                <div><h2 class="text-lg font-semibold text-slate-900">{{ $package->name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $package->quota }} lowongan · {{ $package->duration_days }} hari</p></div>
                <p class="admin-number text-lg font-semibold text-slate-900">Rp {{ number_format($package->price, 0, ',', '.') }}</p>
                <span class="text-xs font-medium text-slate-500">Referensi</span>
            </div>
        @empty
            <x-admin.empty-state title="Belum ada paket aktif" description="Katalog akan tampil setelah paket tersedia di sistem." />
        @endforelse
    </section>
@endsection
