@props(['title', 'description' => null, 'href' => null, 'action' => null])
<div {{ $attributes->merge(['class' => 'px-5 py-12 text-center']) }}>
    <p class="text-base font-semibold text-slate-900">{{ $title }}</p>
    @if ($description)<p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-600">{{ $description }}</p>@endif
    @if ($href && $action)<a href="{{ $href }}" class="admin-button-secondary mt-5">{{ $action }}</a>@endif
</div>
