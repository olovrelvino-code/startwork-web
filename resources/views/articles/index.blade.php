@extends('layouts.public')

@section('title', 'Artikel / News')

@section('content')
    <div class="mx-auto max-w-6xl px-5 py-10 sm:px-8 sm:py-14">
        <header class="mb-8 max-w-2xl border-l-4 border-[var(--jobseeker-accent)] pl-5 sm:mb-10">
            <p class="mb-2 text-xs font-bold uppercase tracking-[.18em] text-[var(--jobseeker-primary)]">Wawasan karier</p>
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Artikel / News</h1>
            <p class="mt-3 text-base leading-7 text-slate-600">Informasi dan cerita seputar dunia kerja dari StartWork.</p>
        </header>

        <form action="{{ route('articles.index') }}" method="GET" role="search" class="mb-8 max-w-lg sm:mb-10">
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search ?? '' }}"
                        placeholder="Cari artikel berdasarkan judul atau konten..."
                        maxlength="255"
                        class="w-full rounded-lg border-slate-300 py-2.5 pl-4 pr-10 text-sm placeholder:text-slate-400 focus:border-[var(--jobseeker-primary)] focus:ring-1 focus:ring-[var(--jobseeker-primary)]"
                        aria-label="Cari artikel berdasarkan judul atau konten"
                    >
                    @if (!empty($search))
                        <a href="{{ route('articles.index') }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400 hover:text-slate-600 focus-visible:outline focus-visible:outline-2" title="Hapus pencarian" aria-label="Hapus kata kunci pencarian">✕</a>
                    @endif
                </div>
                <button
                    type="submit"
                    aria-label="Cari artikel"
                    title="Cari artikel"
                    class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-lg bg-[var(--jobseeker-primary)] text-white hover:bg-[var(--jobseeker-primary-dark)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)]"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
            @if (!empty($search))
                <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                    <span>Hasil pencarian untuk &ldquo;<strong class="font-semibold text-slate-800">{{ $search }}</strong>&rdquo;</span>
                    <a href="{{ route('articles.index') }}" class="font-semibold text-[var(--jobseeker-primary)] underline hover:text-[var(--jobseeker-primary-dark)]">Tampilkan semua artikel</a>
                </div>
            @endif
        </form>

        @if ($articles->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center text-slate-600">
                @if (!empty($search))
                    <p class="text-base font-semibold text-slate-800">Tidak ada artikel yang cocok dengan kata kunci &ldquo;{{ $search }}&rdquo;.</p>
                    <p class="mt-2 text-sm text-slate-500">Coba gunakan kata kunci lain atau telusuri seluruh artikel yang tersedia.</p>
                    <div class="mt-5">
                        <a href="{{ route('articles.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)]">
                            Lihat semua artikel
                        </a>
                    </div>
                @else
                    Belum ada artikel yang diterbitkan.
                @endif
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <article class="jobseeker-article-card group flex min-w-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white">
                        @if ($article->cover_url)
                            <a href="{{ route('articles.show', $article->slug) }}" tabindex="-1" aria-hidden="true" class="overflow-hidden"><img src="{{ $article->cover_url }}" alt="" loading="lazy" width="720" height="420" class="jobseeker-card-image aspect-[12/7] w-full object-cover"></a>
                        @endif
                        <div class="flex flex-1 flex-col p-5">
                            <time class="text-xs font-semibold uppercase tracking-wider text-slate-500" datetime="{{ \Illuminate\Support\Carbon::parse($article->published_at)->toDateString() }}">{{ \Illuminate\Support\Carbon::parse($article->published_at)->translatedFormat('d F Y') }}</time>
                            <h2 class="mt-3 text-xl font-bold leading-snug"><a href="{{ route('articles.show', $article->slug) }}" class="hover:text-[var(--jobseeker-primary)] focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)]">{{ $article->title }}</a></h2>
                            @if ($article->excerpt)<p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ $article->excerpt }}</p>@endif
                            <a href="{{ route('articles.show', $article->slug) }}" class="mt-auto pt-5 text-sm font-semibold text-[var(--jobseeker-primary)] underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)]">Baca selengkapnya <span aria-hidden="true">→</span></a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-8">{{ $articles->links() }}</div>
        @endif
    </div>
@endsection
