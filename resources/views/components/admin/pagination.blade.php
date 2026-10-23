@props(['paginator'])
@if ($paginator->hasPages())
    <nav {{ $attributes->merge(['class' => 'mt-5 flex items-center justify-between gap-3 text-sm']) }} aria-label="Navigasi halaman">
        <span class="text-slate-500">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}</span>
        <div class="flex gap-2">
            @if ($paginator->onFirstPage())<span class="admin-button-secondary opacity-50">Sebelumnya</span>@else<a class="admin-button-secondary" href="{{ $paginator->previousPageUrl() }}">Sebelumnya</a>@endif
            @if ($paginator->hasMorePages())<a class="admin-button-secondary" href="{{ $paginator->nextPageUrl() }}">Berikutnya</a>@else<span class="admin-button-secondary opacity-50">Berikutnya</span>@endif
        </div>
    </nav>
@endif
