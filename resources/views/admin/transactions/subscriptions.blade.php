@extends('layouts.admin')
@section('title', 'Status Subscription')
@section('section_label', 'Kelola Subscription')
@section('content')
    <x-admin.page-header
        title="Status Subscription"
        description="Masa berlaku dan kuota paket seluruh perusahaan terdaftar."
    />

    <nav class="mb-5 flex gap-4 overflow-x-auto border-b border-slate-200" aria-label="Filter status subscription">
        @foreach (['all' => 'Semua', 'active' => 'Aktif', 'pending_activation' => 'Antrean', 'expired' => 'Expired', 'cancelled' => 'Cancelled'] as $value => $label)
            @php($isActive = ($status === null && $value === 'all') || ($status === $value))
            <a
                href="{{ route('admin.transactions.subscriptions', array_filter(['status' => $value === 'all' ? null : $value, 'search' => $search ?? null])) }}"
                @class([
                    'shrink-0 border-b-2 pb-3 text-sm font-semibold transition-colors',
                    'border-[#DE7C23] text-slate-900' => $isActive,
                    'border-transparent text-slate-500 hover:text-slate-800' => ! $isActive,
                ])
                @if ($isActive) aria-current="page" @endif
            >
                {{ $label }}
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('admin.transactions.subscriptions') }}" class="mb-6 flex flex-wrap items-center gap-3">
        @if ($status)
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <div class="relative min-w-[260px] flex-1 max-w-md">
            <label class="sr-only" for="subscription-search">Cari perusahaan</label>
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg aria-hidden="true" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
            <input
                id="subscription-search"
                name="search"
                type="search"
                value="{{ $search ?? '' }}"
                placeholder="Cari nama perusahaan..."
                class="admin-input w-full pl-9"
            >
        </div>

        <button class="admin-button-primary" type="submit">Cari</button>

        @if (!empty($search) || !empty($status))
            <a href="{{ route('admin.transactions.subscriptions') }}" class="admin-button-secondary text-xs">Reset</a>
        @endif
    </form>

    <div class="mb-3 flex items-center justify-between text-xs text-slate-500">
        <span>Menampilkan {{ number_format($subscriptions->total(), 0, ',', '.') }} subscription perusahaan</span>
    </div>

    <x-admin.data-table class="rounded-xl border border-slate-200/90 shadow-md">
        <x-slot:mobile>
            @forelse ($subscriptions as $sub)
                <div class="p-4 transition-colors">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <strong class="block truncate text-sm font-semibold text-[#17394B]">{{ $sub->company_name }}</strong>
                            <span class="text-xs text-slate-600">{{ $sub->package_name }}</span>
                        </div>
                        <x-admin.status-badge :status="$sub->status" />
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-100 pt-2 text-xs text-slate-600">
                        <div>
                            <span class="text-slate-400 block text-[11px]">Kuota</span>
                            <span class="font-semibold text-slate-800">{{ $sub->quota_used ?? 0 }} / {{ $sub->quota_total ?? 0 }} (sisa {{ max(0, ($sub->quota_total ?? 0) - ($sub->quota_used ?? 0)) }})</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Berakhir</span>
                            <span class="font-semibold text-slate-800">{{ $sub->expires_at ? \Carbon\Carbon::parse($sub->expires_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : '—' }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <p class="p-6 text-center text-sm text-slate-500">Belum ada subscription yang sesuai.</p>
            @endforelse
        </x-slot:mobile>

        <table class="admin-table">
            <thead>
                <tr>
                    <th class="w-12 text-center">No.</th>
                    <th>Perusahaan</th>
                    <th>Paket</th>
                    <th>Status</th>
                    <th>Penggunaan Kuota</th>
                    <th>Periode Aktif</th>
                    <th class="text-right">Dibuat</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscriptions as $index => $sub)
                    <tr>
                        <td class="text-center text-xs font-mono text-slate-400">
                            {{ $subscriptions->firstItem() ? ($subscriptions->firstItem() + $index) : ($index + 1) }}.
                        </td>
                        <td>
                            <strong class="block font-semibold text-[#17394B]">{{ $sub->company_name }}</strong>
                            <span class="font-mono text-[11px] text-slate-400">{{ Str::limit($sub->company_id, 13) }}</span>
                        </td>
                        <td>
                            <span class="font-medium text-slate-700">{{ $sub->package_name }}</span>
                        </td>
                        <td>
                            <x-admin.status-badge :status="$sub->status" />
                        </td>
                        <td>
                            <div class="text-xs">
                                <span class="font-bold text-slate-900">{{ max(0, ($sub->quota_total ?? 0) - ($sub->quota_used ?? 0)) }} sisa</span>
                                <span class="text-slate-500">({{ $sub->quota_used ?? 0 }} terpakai dari {{ $sub->quota_total ?? 0 }})</span>
                            </div>
                        </td>
                        <td>
                            <div class="text-xs text-slate-600">
                                <div><span class="text-slate-400">Mulai:</span> {{ $sub->starts_at ? \Carbon\Carbon::parse($sub->starts_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : 'Menunggu' }}</div>
                                <div><span class="text-slate-400">Selesai:</span> {{ $sub->expires_at ? \Carbon\Carbon::parse($sub->expires_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : '—' }}</div>
                            </div>
                        </td>
                        <td class="text-right text-xs text-slate-500">
                            {{ \Carbon\Carbon::parse($sub->created_at)->format('d M Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-sm text-slate-500">Belum ada subscription yang terdaftar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin.data-table>

    <div class="mt-5">
        <x-admin.pagination :paginator="$subscriptions" />
    </div>
@endsection
