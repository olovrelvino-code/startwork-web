@props(['paginator', 'always' => false, 'english' => false, 'numbered' => false, 'round' => false])
@if ($paginator->hasPages() || $always || $numbered || $round)
    <nav {{ $attributes->merge(['class' => 'mt-5 flex flex-wrap items-center justify-between gap-3 text-sm']) }} aria-label="Navigasi halaman">
        <div class="text-slate-600">
            <span>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }}</span>
            @if ($always || $numbered || $round)<span class="ml-3 whitespace-nowrap">{{ $english ? 'Page' : 'Halaman' }} {{ $paginator->currentPage() }} {{ $english ? 'of' : 'dari' }} {{ $paginator->lastPage() }}</span>@endif
        </div>
        <div class="flex max-w-full items-center gap-1 overflow-x-auto pb-1 [&>*]:shrink-0">
            @unless ($round)
                @if ($paginator->onFirstPage())<span class="admin-button-secondary opacity-50" aria-disabled="true" @if ($numbered) aria-label="Halaman sebelumnya" @endif>{{ $numbered ? '‹' : 'Sebelumnya' }}</span>@else<a class="admin-button-secondary" href="{{ $paginator->previousPageUrl() }}" @if ($numbered) aria-label="Halaman sebelumnya" @endif>{{ $numbered ? '‹' : 'Sebelumnya' }}</a>@endif
            @endunless
            @if ($numbered || $round)
                @php
                    $last = $paginator->lastPage();
                    $width = $round ? 4 : 3;
                    $start = max(1, min($paginator->currentPage() - 1, $last - $width + 1));
                    $end = min($last, $start + $width - 1);
                    $pageClass = $round ? 'inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-300 bg-white font-semibold text-slate-700 transition-colors hover:border-[#B85B12] hover:bg-[#FBF4EE] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#2B506B]' : 'admin-button-secondary';
                    $activeClass = $round ? 'inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#B85B12] bg-[#B85B12] font-semibold text-white' : 'admin-button-primary';
                @endphp
                @if ($start > 1)<a class="{{ $pageClass }}" href="{{ $paginator->url(1) }}" aria-label="Halaman 1">1</a>@endif
                @if ($start > 2)<span class="px-1 text-slate-500" aria-hidden="true">…</span>@endif
                @for ($page = $start; $page <= $end; $page++)
                    @if ($page === $paginator->currentPage())<span class="{{ $activeClass }}" aria-current="page" aria-label="Halaman {{ $page }}">{{ $page }}</span>@else<a class="{{ $pageClass }}" href="{{ $paginator->url($page) }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>@endif
                @endfor
                @if ($end < $last - 1)<span class="px-1 text-slate-500" aria-hidden="true">…</span>@endif
                @if ($end < $last)<a class="{{ $pageClass }}" href="{{ $paginator->url($last) }}" aria-label="Halaman {{ $last }}">{{ $last }}</a>@endif
            @endif
            @unless ($round)
                @if ($paginator->hasMorePages())<a class="admin-button-secondary" href="{{ $paginator->nextPageUrl() }}" @if ($numbered) aria-label="Halaman berikutnya" @endif>{{ $numbered ? '›' : 'Berikutnya' }}</a>@else<span class="admin-button-secondary opacity-50" aria-disabled="true" @if ($numbered) aria-label="Halaman berikutnya" @endif>{{ $numbered ? '›' : 'Berikutnya' }}</span>@endif
            @endunless
        </div>
    </nav>
@endif
