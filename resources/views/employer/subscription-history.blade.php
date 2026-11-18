@extends('layouts.employer')

@section('title', 'Riwayat Pembelian')
@section('section_label', 'Riwayat Pembelian')

@section('content')
    <div class="mb-7 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-[#B85B12]">{{ $company->name }}</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-[#17394B] sm:text-3xl">Riwayat Pembelian</h1>
            <p class="mt-2 text-sm text-slate-600">Pantau verifikasi paket dan kirim bukti pembayaran dari sini.</p>
        </div>
        <a href="{{ route('employer.subscription') }}" class="admin-button-quiet">Lihat paket</a>
    </div>

    @if (session('success'))
        <p role="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</p>
    @endif
    @error('proof')
        <p role="alert" class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">{{ $message }}</p>
    @enderror

    <div class="space-y-4">
        @forelse ($purchases as $purchase)
            @php
                $status = match ($purchase->status) {
                    'pending_payment' => 'Menunggu pembayaran',
                    'waiting_verification' => 'Menunggu verifikasi',
                    'paid' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'cancelled' => 'Dibatalkan',
                    default => $purchase->status,
                };
            @endphp
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-[#17394B]">Paket {{ $purchase->package_name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ \Illuminate\Support\Carbon::parse($purchase->created_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB · ID {{ $purchase->id }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full bg-[#F3F7F8] px-3 py-1 text-xs font-semibold text-[#17394B]">{{ $status }}</span>
                        @if ($purchase->status === 'paid')
                            <span @class(['rounded-full px-3 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-800' => $purchase->subscription_status === 'active', 'bg-amber-50 text-amber-800' => $purchase->subscription_status === 'pending_activation', 'bg-slate-100 text-slate-600' => ! in_array($purchase->subscription_status, ['active', 'pending_activation'])])>{{ match ($purchase->subscription_status) { 'active' => 'Aktif', 'pending_activation' => 'Menunggu aktivasi', 'expired' => 'Berakhir', default => ucfirst($purchase->subscription_status) } }}</span>
                        @endif
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 border-t border-slate-100 pt-4 text-sm text-slate-700">
                    <span class="font-semibold text-[#17394B]">Rp {{ number_format($purchase->amount, 0, ',', '.') }}</span>
                    <span>{{ $purchase->quota_total }} lowongan</span>
                    <span>{{ $purchase->duration_days }} hari</span>
                </div>
                @if ($purchase->verification_note)
                    <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">Catatan admin: {{ $purchase->verification_note }}</p>
                @endif
                @if ($purchase->status === 'pending_payment')
                    <div class="mt-5 border-t border-slate-100 pt-5">
                        <form id="proof-form-{{ $purchase->id }}" data-proof-form method="POST" action="{{ route('employer.subscription.purchases.proof', $purchase->id) }}" enctype="multipart/form-data">
                            @csrf
                            <label class="admin-label" for="proof-{{ $purchase->id }}">Bukti pembayaran</label>
                            <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center">
                                <input id="proof-{{ $purchase->id }}" name="proof" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required aria-describedby="proof-help-{{ $purchase->id }} proof-status-{{ $purchase->id }}" class="admin-input min-w-0 flex-1 p-0 file:mr-3 file:border-0 file:bg-[#F3F7F8] file:px-3 file:py-3 file:text-sm file:font-semibold file:text-[#17394B]">
                                <button type="submit" class="admin-button-primary w-full shrink-0 sm:w-auto">Kirim bukti</button>
                                <button type="submit" form="cancel-form-{{ $purchase->id }}" class="admin-button-quiet w-full shrink-0 sm:w-auto">Batalkan pesanan</button>
                            </div>
                            <p id="proof-help-{{ $purchase->id }}" class="mt-2 text-xs text-slate-600">JPG, JPEG, PNG, WEBP maks. 20 MB; PDF maks. 2 MB.</p>
                            <p id="proof-status-{{ $purchase->id }}" data-proof-status role="status" aria-live="polite" class="mt-1 break-all text-xs text-slate-700">Belum ada berkas dipilih.</p>
                        </form>
                        <form id="cancel-form-{{ $purchase->id }}" method="POST" action="{{ route('employer.subscription.purchases.cancel', $purchase->id) }}">@csrf</form>
                    </div>
                @endif
            </article>
        @empty
            <x-admin.empty-state title="Belum ada pembelian" description="Paket yang Anda pesan akan muncul di sini." />
        @endforelse
    </div>
    <div class="mt-6"><x-admin.pagination :paginator="$purchases" :numbered="true" /></div>
    <div data-proof-toast role="status" aria-live="polite" class="fixed bottom-4 right-4 z-50 hidden max-w-[calc(100vw-2rem)] items-center gap-3 rounded-lg border border-emerald-300 bg-white px-4 py-3 text-sm text-emerald-900 shadow-lg sm:bottom-6 sm:right-6">
        <span aria-hidden="true" class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-emerald-100 font-bold">✓</span>
        <span>Bukti pembayaran berhasil dikirim untuk verifikasi admin.</span>
    </div>
@endsection
