@extends('layouts.admin')
@section('title', 'Dashboard Subscription')
@section('section_label', 'Kelola Subscription')
@section('content')
    <x-admin.page-header title="Dashboard Subscription" description="Ringkasan pembayaran dan status subscription StartWork saat ini." />

    <div class="mb-3">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500">Status Transaksi</h2>
    </div>
    <section class="mb-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 xl:grid-cols-6" aria-label="Ringkasan transaksi">
        @php
            $transactionCardStyles = [
                'waiting_verification' => [
                    'label' => 'Waiting Verification',
                    'sublabel' => 'Perlu Verifikasi',
                    'border' => 'border-t-4 border-amber-500',
                    'badge' => 'bg-amber-100 text-amber-800',
                    'count_color' => 'text-amber-600',
                    'hover_shadow' => 'hover:shadow-amber-100/50',
                ],
                'pending_payment' => [
                    'label' => 'Pending Payment',
                    'sublabel' => 'Menunggu Bayar',
                    'border' => 'border-t-4 border-sky-500',
                    'badge' => 'bg-sky-100 text-sky-800',
                    'count_color' => 'text-sky-600',
                    'hover_shadow' => 'hover:shadow-sky-100/50',
                ],
                'paid' => [
                    'label' => 'Paid',
                    'sublabel' => 'Selesai / Lunas',
                    'border' => 'border-t-4 border-emerald-500',
                    'badge' => 'bg-emerald-100 text-emerald-800',
                    'count_color' => 'text-emerald-600',
                    'hover_shadow' => 'hover:shadow-emerald-100/50',
                ],
                'rejected' => [
                    'label' => 'Rejected',
                    'sublabel' => 'Ditolak',
                    'border' => 'border-t-4 border-rose-500',
                    'badge' => 'bg-rose-100 text-rose-800',
                    'count_color' => 'text-rose-600',
                    'hover_shadow' => 'hover:shadow-rose-100/50',
                ],
                'failed' => [
                    'label' => 'Failed',
                    'sublabel' => 'Gagal',
                    'border' => 'border-t-4 border-red-400',
                    'badge' => 'bg-red-100 text-red-800',
                    'count_color' => 'text-red-600',
                    'hover_shadow' => 'hover:shadow-red-100/50',
                ],
                'cancelled' => [
                    'label' => 'Cancelled',
                    'sublabel' => 'Dibatalkan',
                    'border' => 'border-t-4 border-slate-400',
                    'badge' => 'bg-slate-100 text-slate-700',
                    'count_color' => 'text-slate-600',
                    'hover_shadow' => 'hover:shadow-slate-100/50',
                ],
            ];
        @endphp
        @foreach ($transactionCardStyles as $status => $cfg)
            <a href="{{ route('admin.transactions.history', ['status' => $status]) }}" class="group relative flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-md {{ $cfg['border'] }} {{ $cfg['hover_shadow'] }}">
                <div>
                    <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span>
                    <p class="mt-1 text-xs text-slate-500">{{ $cfg['sublabel'] }}</p>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <strong class="text-2xl font-bold {{ $cfg['count_color'] }}">{{ number_format($counts[$status] ?? 0, 0, ',', '.') }}</strong>
                    <span class="text-xs text-slate-400 group-hover:text-slate-600">Lihat →</span>
                </div>
            </a>
        @endforeach
    </section>

    <div class="mb-3">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500">Status Paket Perusahaan</h2>
    </div>
    <section class="mb-8 grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4" aria-label="Ringkasan subscription">
        @php
            $subCardStyles = [
                'active' => [
                    'label' => 'Aktif',
                    'desc' => 'Perusahaan dengan kuota aktif berjalan',
                    'border' => 'border-t-4 border-[#21546F]',
                    'badge' => 'bg-emerald-100 text-emerald-800',
                    'count_color' => 'text-[#21546F]',
                ],
                'pending_activation' => [
                    'label' => 'Antrean',
                    'desc' => 'Sudah bayar, aktif setelah paket lama habis',
                    'border' => 'border-t-4 border-[#DE7C23]',
                    'badge' => 'bg-amber-100 text-amber-800',
                    'count_color' => 'text-[#DE7C23]',
                ],
                'expired' => [
                    'label' => 'Expired',
                    'desc' => 'Masa berlaku atau kuota telah habis',
                    'border' => 'border-t-4 border-slate-400',
                    'badge' => 'bg-slate-100 text-slate-700',
                    'count_color' => 'text-slate-600',
                ],
            ];
        @endphp
        @foreach ($subCardStyles as $status => $cfg)
            <a href="{{ route('admin.transactions.subscriptions', ['status' => $status]) }}" class="group relative flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-md {{ $cfg['border'] }}">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span>
                        <span class="text-xs text-slate-400 group-hover:text-slate-600">Kelola →</span>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ $cfg['desc'] }}</p>
                </div>
                <strong class="mt-4 block text-3xl font-bold {{ $cfg['count_color'] }}">{{ number_format($subscriptions[$status] ?? 0, 0, ',', '.') }}</strong>
            </a>
        @endforeach
    </section>

    <div class="mt-10">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-lg font-bold text-[#17394B]">Transaksi Terbaru</h2>
                <p class="text-xs text-slate-500">8 transaksi pembayaran yang baru masuk sistem.</p>
            </div>
            <a href="{{ route('admin.transactions.history') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-[#17394B] shadow-sm hover:border-[#21546F] hover:bg-slate-50 transition-colors">
                <span>Lihat Seluruh Riwayat</span>
                <span aria-hidden="true">→</span>
            </a>
        </div>

        <x-admin.data-table class="rounded-xl border border-slate-200/90 shadow-md">
            <x-slot:mobile>
                @forelse ($recent as $transaction)
                    <div class="p-4 transition-colors">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <strong class="block text-sm font-semibold text-[#17394B]">{{ $transaction->company_name }}</strong>
                                <span class="text-xs text-slate-500">{{ $transaction->package_name }} · {{ \Carbon\Carbon::parse($transaction->created_at)->format('d M Y, H:i') }}</span>
                            </div>
                            <x-admin.status-badge :status="$transaction->status" />
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs">
                            <span class="font-bold text-[#17394B]">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                            <a href="{{ route('admin.transactions.show', $transaction->id) }}" class="font-semibold text-[#DE7C23] hover:underline">Lihat Detail →</a>
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-center text-sm text-slate-500">Belum ada transaksi.</p>
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
                        <th>Waktu Transaksi</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $index => $transaction)
                        <tr>
                            <td class="text-center text-xs font-mono text-slate-400">{{ $index + 1 }}.</td>
                            <td>
                                <strong class="block font-semibold text-[#17394B]">{{ $transaction->company_name }}</strong>
                                <span class="text-[11px] font-mono text-slate-400">{{ Str::limit($transaction->id, 13) }}</span>
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
                            <td colspan="7" class="p-8 text-center text-sm text-slate-500">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-admin.data-table>
    </div>
@endsection
