@props(['status'])
@php
    [$label, $style] = match ($status) {
        'active' => ['Aktif', 'bg-emerald-50 text-emerald-800'],
        'inactive' => ['Nonaktif', 'bg-slate-100 text-slate-700'],
        'expired' => ['Kedaluwarsa', 'bg-rose-50 text-rose-800'],
        'submitted' => ['Terkirim', 'bg-blue-50 text-blue-800'],
        'in_process' => ['Diproses', 'bg-amber-50 text-amber-800'],
        'accepted' => ['Diterima', 'bg-emerald-50 text-emerald-800'],
        'rejected' => ['Ditolak', 'bg-rose-50 text-rose-800'],
        'draft' => ['Draf', 'bg-slate-100 text-slate-700'],
        'published' => ['Terbit', 'bg-emerald-50 text-emerald-800'],
        default => [ucfirst(str_replace('_', ' ', $status)), 'bg-slate-100 text-slate-700'],
    };
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex whitespace-nowrap rounded-md px-2.5 py-1 text-xs font-semibold '.$style]) }}>{{ $label }}</span>
