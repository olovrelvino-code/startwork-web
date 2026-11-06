@props(['title', 'description' => null, 'back' => null])
<div {{ $attributes->merge(['class' => 'mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="admin-link mb-3 inline-block text-xs">← Kembali</a>
        @endif
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
        @if ($description)<p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600">{{ $description }}</p>@endif
    </div>
    @isset($actions)<div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
</div>
