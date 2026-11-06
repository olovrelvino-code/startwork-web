@extends('layouts.public')

@section('title', 'Artikel / News')

@section('content')
    <section class="jobseeker-jobs-banner bg-[var(--jobseeker-primary-dark)] text-white" aria-labelledby="articles-banner-title">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
            <div class="max-w-3xl border-l-4 border-[var(--jobseeker-accent)] pl-5">
                <h1 id="articles-banner-title" class="text-3xl font-bold tracking-tight sm:text-4xl">Wawasan untuk langkah kariermu.</h1>
                <p class="mt-3 text-sm leading-7 text-slate-200 sm:text-base">Panduan dan cerita seputar dunia kerja dari StartWork.</p>
            </div>
        </div>
    </section>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-8"><h2 class="text-2xl font-bold tracking-tight">Artikel / News</h2><p class="mt-2 text-slate-600">Temukan informasi dan panduan karier terbaru.</p></header>

        <div class="mb-8 flex flex-col gap-4 sm:mb-10 sm:flex-row sm:items-end sm:justify-between">
            <form action="{{ route('articles.index') }}" method="GET" role="search" class="w-full max-w-md">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <div class="relative">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search ?? '' }}"
                        placeholder="Cari artikel berdasarkan judul atau konten..."
                        maxlength="255"
                        class="min-h-11 w-full rounded-lg border-slate-300 py-2.5 pl-4 {{ !empty($search) ? 'pr-20' : 'pr-12' }} text-sm placeholder:text-slate-400 focus:border-[var(--jobseeker-primary)] focus:ring-1 focus:ring-[var(--jobseeker-primary)]"
                        aria-label="Cari artikel berdasarkan judul atau konten"
                    >
                    @if (!empty($search))
                        <a href="{{ route('articles.index', ['sort' => $sort]) }}" class="absolute right-12 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400 hover:text-slate-600 focus-visible:outline focus-visible:outline-2" title="Hapus pencarian" aria-label="Hapus kata kunci pencarian">×</a>
                    @endif
                    <button type="submit" aria-label="Cari artikel" title="Cari artikel" class="absolute right-1 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-md bg-[#B85B12] text-white transition-colors hover:bg-[#8A430A] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 4.2 4.2"></path></svg></button>
                </div>
                @if (!empty($search))
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                        <span>Hasil pencarian untuk &ldquo;<strong class="font-semibold text-slate-800">{{ $search }}</strong>&rdquo;</span>
                        <a href="{{ route('articles.index', ['sort' => $sort]) }}" class="font-semibold text-[var(--jobseeker-primary)] underline hover:text-[var(--jobseeker-primary-dark)]">Tampilkan semua artikel</a>
                    </div>
                @endif
            </form>
            <nav class="inline-flex w-fit rounded-lg bg-slate-200 p-1" aria-label="Urutkan artikel">
                <a href="{{ route('articles.index', array_filter(['q' => $search, 'sort' => 'latest'])) }}" @if ($sort === 'latest') aria-current="page" @endif class="rounded-md px-4 py-2 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 {{ $sort === 'latest' ? 'bg-white text-slate-900' : 'text-slate-600 hover:text-slate-900' }}">Latest</a>
                <a href="{{ route('articles.index', array_filter(['q' => $search, 'sort' => 'oldest'])) }}" @if ($sort === 'oldest') aria-current="page" @endif class="rounded-md px-4 py-2 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 {{ $sort === 'oldest' ? 'bg-white text-slate-900' : 'text-slate-600 hover:text-slate-900' }}">Oldest</a>
            </nav>
        </div>

        @if ($articles->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center text-slate-600">
                @if (!empty($search))
                    <p class="text-base font-semibold text-slate-800">Tidak ada artikel yang cocok dengan kata kunci &ldquo;{{ $search }}&rdquo;.</p>
                    <p class="mt-2 text-sm text-slate-500">Coba gunakan kata kunci lain atau telusuri seluruh artikel yang tersedia.</p>
                    <div class="mt-5">
                        <a href="{{ route('articles.index', ['sort' => $sort]) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)]">
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
