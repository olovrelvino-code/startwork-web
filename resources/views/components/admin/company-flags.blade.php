@props(['createdAt', 'trialExpiresAt' => null, 'trialStatus' => null, 'ownerRole' => null])

<span class="inline-flex flex-wrap gap-1.5">
    @if (\Illuminate\Support\Carbon::parse($createdAt)->greaterThan(now()->subDay()) && \Illuminate\Support\Carbon::parse($createdAt)->lessThanOrEqualTo(now()))
        <span class="rounded border border-[#DE7C23]/30 bg-[#DE7C23]/10 px-2 py-0.5 text-[11px] font-bold tracking-wide text-[#95420D]">NEW</span>
    @endif
    @if ($trialExpiresAt)
        <span @class(['rounded border px-2 py-0.5 text-[11px] font-semibold', 'border-emerald-200 bg-emerald-50 text-emerald-800' => $trialStatus === 'active' && \Illuminate\Support\Carbon::parse($trialExpiresAt)->isFuture(), 'border-slate-200 bg-slate-50 text-slate-600' => $trialStatus !== 'active' || ! \Illuminate\Support\Carbon::parse($trialExpiresAt)->isFuture()])>{{ $trialStatus === 'revoked' ? 'Trial dicabut' : ($trialStatus === 'active' && \Illuminate\Support\Carbon::parse($trialExpiresAt)->isFuture() ? 'Trial aktif' : 'Trial berakhir') }}</span>
    @elseif ($ownerRole === 'employer')
        <span class="rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Belum trial</span>
    @endif
</span>
