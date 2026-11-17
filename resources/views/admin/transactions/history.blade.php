@extends('layouts.admin')
@php($isRequest = request()->routeIs('admin.transactions.requests'))
@section('title', $isRequest ? 'Permintaan Verifikasi' : 'Riwayat Transaksi')
@section('section_label', 'Kelola Subscription')
@section('content')
    <x-admin.page-header
        :title="$isRequest ? 'Permintaan Verifikasi' : 'Riwayat Transaksi'"
        :description="$isRequest ? 'Daftar pembayaran yang memerlukan tinjauan dan konfirmasi admin.' : 'Telusuri seluruh riwayat transaksi pembelian paket StartWork.'"
    />

    <form method="GET" class="mb-6 flex flex-wrap items-center gap-3">
        <div class="relative min-w-[260px] flex-1 max-w-md">
            <label class="sr-only" for="transaction-search">Cari perusahaan atau transaksi</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg aria-hidden="true" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input
                id="transaction-search"
                name="search"
                type="search"
                value="{{ $filters['search'] ?? '' }}"
                placeholder="Cari nama perusahaan, paket, atau ID..."
                class="admin-input w-full pl-9"
            >
        </div>

        @unless ($isRequest)
            <div class="w-auto">
                <label class="sr-only" for="transaction-status">Status Transaksi</label>
                <select id="transaction-status" name="status" class="admin-input w-auto pr-8">
                    <option value="">Semua Status</option>
                    @foreach (['waiting_verification' => 'Waiting Verification', 'pending_payment' => 'Pending Payment', 'paid' => 'Paid', 'rejected' => 'Rejected', 'failed' => 'Failed', 'cancelled' => 'Cancelled'] as $status => $label)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endunless

        <button class="admin-button-primary" type="submit">Cari</button>

        @if (!empty($filters['search']) || !empty($filters['status']))
            <a href="{{ $isRequest ? route('admin.transactions.requests') : route('admin.transactions.history') }}" class="admin-button-secondary text-xs">Reset</a>
        @endif
    </form>

    <div class="mb-3 flex items-center justify-between text-xs text-slate-500">
        <span>Menampilkan {{ number_format($transactions->total(), 0, ',', '.') }} transaksi</span>
    </div>

    <x-admin.data-table class="rounded-xl border border-slate-200/90 shadow-md">
        <x-slot:mobile>
            @forelse ($transactions as $transaction)
                <div class="p-4 transition-colors">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <strong class="block truncate text-sm font-semibold text-[#17394B]">{{ $transaction->company_name }}</strong>
                            <span class="text-xs text-slate-500">{{ $transaction->package_name }} · {{ \Carbon\Carbon::parse($transaction->created_at)->format('d M Y, H:i') }}</span>
                            <span class="mt-0.5 block font-mono text-[11px] text-slate-400 break-all">{{ $transaction->id }}</span>
                        </div>
                        <x-admin.status-badge :status="$transaction->status" />
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2 text-xs">
                        <span class="font-bold text-[#17394B]">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                        <a href="{{ route('admin.transactions.show', $transaction->id) }}" class="inline-flex items-center gap-1 font-semibold text-[#DE7C23] hover:underline">
                            <span>Periksa Detail</span>
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            @empty
                <p class="p-6 text-center text-sm text-slate-500">Tidak ada transaksi yang sesuai kriteria.</p>
            @endforelse
        </x-slot:mobile>

        <table class="admin-table">
            <thead>
                <tr>
                    <th class="w-12 text-center">No.</th>
                    <th>Perusahaan</th>
                    <th>Paket</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th>Waktu Pengajuan</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $index => $transaction)
                    <tr>
                        <td class="text-center text-xs font-mono text-slate-400">
                            {{ $transactions->firstItem() ? ($transactions->firstItem() + $index) : ($index + 1) }}.
                        </td>
                        <td>
                            <strong class="block font-semibold text-[#17394B]">{{ $transaction->company_name }}</strong>
                            <span class="font-mono text-[11px] text-slate-400" title="{{ $transaction->id }}">{{ Str::limit($transaction->id, 16) }}</span>
                        </td>
                        <td>
                            <span class="font-medium text-slate-700">{{ $transaction->package_name }}</span>
                        </td>
                        <td>
                            <span class="font-bold text-slate-900">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                        </td>
                        <td>
                            <x-admin.status-badge :status="$transaction->status" />
                        </td>
                        <td>
                            <span class="text-xs text-slate-600">{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M Y, H:i') }} WIB</span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.transactions.show', $transaction->id) }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-[#17394B] shadow-sm hover:border-[#21546F] hover:bg-slate-50 transition-colors">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-sm text-slate-500">Tidak ada transaksi yang ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin.data-table>

    <div class="mt-5">
        <x-admin.pagination :paginator="$transactions" />
    </div>
@endsection
