@props(['createdAt', 'trialExpiresAt' => null, 'ownerRole' => null])

<span class="inline-flex flex-wrap gap-1.5">
    @if (\Illuminate\Support\Carbon::parse($createdAt)->greaterThan(now()->subDay()) && \Illuminate\Support\Carbon::parse($createdAt)->lessThanOrEqualTo(now()))
        <span class="rounded border border-[#B85B12]/30 bg-[#B85B12]/10 px-2 py-0.5 text-[11px] font-bold tracking-wide text-[#8F460F]">NEW</span>
    @endif
    @if ($trialExpiresAt)
        <span @class(['rounded border px-2 py-0.5 text-[11px] font-semibold', 'border-emerald-200 bg-emerald-50 text-emerald-800' => \Illuminate\Support\Carbon::parse($trialExpiresAt)->isFuture(), 'border-slate-200 bg-slate-50 text-slate-600' => ! \Illuminate\Support\Carbon::parse($trialExpiresAt)->isFuture()])>{{ \Illuminate\Support\Carbon::parse($trialExpiresAt)->isFuture() ? 'Trial aktif' : 'Trial berakhir' }}</span>
    @elseif ($ownerRole === 'employer')
        <span class="rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Belum trial</span>
    @endif
</span>
