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
                    <span class="rounded-full bg-[#F3F7F8] px-3 py-1 text-xs font-semibold text-[#17394B]">{{ $status }}</span>
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
                    <div class="mt-5 flex flex-wrap items-end gap-4 border-t border-slate-100 pt-5">
                        <form method="POST" action="{{ route('employer.subscription.purchases.proof', $purchase->id) }}" enctype="multipart/form-data" class="flex flex-1 flex-wrap items-end gap-3">
                            @csrf
                            <div class="min-w-[200px] flex-1"><label class="admin-label" for="proof-{{ $purchase->id }}">Bukti pembayaran (JPG, PNG, WebP, PDF; maks. 2 MB)</label><input id="proof-{{ $purchase->id }}" name="proof" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required class="admin-input w-full"></div>
                            <button type="submit" class="rounded-lg bg-[#B85B12] px-4 py-3 text-sm font-semibold text-white hover:bg-[#95420D]">Kirim bukti</button>
                        </form>
                        <form method="POST" action="{{ route('employer.subscription.purchases.cancel', $purchase->id) }}">@csrf<button type="submit" class="admin-button-quiet">Batalkan pesanan</button></form>
                    </div>
                @endif
            </article>
        @empty
            <x-admin.empty-state title="Belum ada pembelian" description="Paket yang Anda pesan akan muncul di sini." />
        @endforelse
    </div>
    <div class="mt-6"><x-admin.pagination :paginator="$purchases" :numbered="true" /></div>
@endsection
