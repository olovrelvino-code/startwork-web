@props(['type' => 'info', 'message'])
@php
    $styles = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'error' => 'border-rose-200 bg-rose-50 text-rose-900',
        'warning' => 'border-[#F4CEAA] bg-[#FFF5EB] text-slate-900',
        'info' => 'border-slate-200 bg-slate-50 text-slate-800',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'mb-6 rounded-lg border px-4 py-3 text-sm leading-relaxed '.($styles[$type] ?? $styles['info'])]) }} role="{{ $type === 'error' ? 'alert' : 'status' }}">{{ $message }}</div>
