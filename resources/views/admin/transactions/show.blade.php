@extends('layouts.admin')
@section('title', 'Detail Transaksi')
@section('section_label', 'Kelola Subscription')
@section('content')
    <x-admin.page-header title="Detail Transaksi" :back="route('admin.transactions.history')" />
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.7fr)]">
        <section class="admin-panel shadow-md p-5 sm:p-6" aria-label="Informasi transaksi">
            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                @foreach (['ID' => $transaction->id, 'Perusahaan' => $transaction->company_name, 'Paket' => $transaction->package_name, 'Nominal' => 'Rp '.number_format($transaction->amount, 0, ',', '.'), 'Status pembayaran' => ucwords(str_replace('_', ' ', $transaction->status)), 'Status subscription' => ucwords(str_replace('_', ' ', $transaction->subscription_status)), 'Dibuat' => $transaction->created_at, 'Diverifikasi' => $transaction->verified_at ?? '—'] as $label => $value)
                    <div><dt class="text-slate-500">{{ $label }}</dt><dd class="mt-1 break-all font-semibold text-[#17394B]">{{ $value }}</dd></div>
                @endforeach
            </dl>
            @if ($transaction->proof_path)<a href="{{ route('admin.transactions.proof', $transaction->id) }}" target="_blank" rel="noopener" class="admin-button-secondary mt-6">Lihat bukti pembayaran</a>@else<p class="mt-6 text-sm text-slate-500">Bukti pembayaran belum dikirim.</p>@endif
            @if ($transaction->status === 'waiting_verification')
                <form method="POST" action="{{ route('admin.transactions.decide', $transaction->id) }}" class="mt-6 border-t border-slate-200 pt-5">@csrf
                    <label for="decision-note" class="admin-label">Catatan verifikasi</label><textarea id="decision-note" name="note" maxlength="500" rows="3" class="admin-input mb-4"></textarea>
                    <div class="flex flex-wrap gap-3"><button name="decision" value="approve" class="admin-button-primary" type="submit">Approve pembayaran</button><button name="decision" value="reject" class="admin-button-secondary" type="submit">Reject pembayaran</button></div>
                </form>
            @endif
        </section>
        <section class="admin-panel p-5 sm:p-6" aria-label="Audit trail"><h2 class="mb-4 text-lg font-semibold text-[#17394B]">Riwayat keputusan</h2><ol class="space-y-4">@forelse ($events as $event)<li class="border-l-2 border-[#DE7C23] pl-3 text-sm"><strong class="block">{{ ucwords(str_replace('_', ' ', $event->to_status)) }}</strong><span class="text-slate-600">{{ $event->created_at }} · {{ $event->actor_name ?: 'Sistem' }}</span>@if ($event->note)<p class="mt-1">{{ $event->note }}</p>@endif</li>@empty<li class="text-sm text-slate-600">Belum ada aktivitas.</li>@endforelse</ol></section>
    </div>
@endsection
