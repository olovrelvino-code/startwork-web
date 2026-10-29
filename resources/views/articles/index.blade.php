@extends('layouts.public')

@section('title', 'Artikel / News')

@section('content')
    <div class="mx-auto max-w-6xl px-5 py-10 sm:px-8 sm:py-14">
        <header class="mb-8 max-w-2xl border-l-4 border-[#be6332] pl-5 sm:mb-10">
            <p class="mb-2 text-xs font-bold uppercase tracking-[.18em] text-[#a54d25]">Wawasan karier</p>
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Artikel / News</h1>
            <p class="mt-3 text-base leading-7 text-slate-600">Informasi dan cerita seputar dunia kerja dari StartWork.</p>
        </header>

        @if ($articles->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center text-slate-600">Belum ada artikel yang diterbitkan.</div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <article class="flex min-w-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white transition-colors hover:border-[#be6332]">
                        @if ($article->cover_url)
                            <a href="{{ route('articles.show', $article->slug) }}" tabindex="-1" aria-hidden="true"><img src="{{ $article->cover_url }}" alt="" loading="lazy" width="720" height="420" class="aspect-[12/7] w-full object-cover"></a>
                        @endif
                        <div class="flex flex-1 flex-col p-5">
                            <time class="text-xs font-semibold uppercase tracking-wider text-slate-500" datetime="{{ \Illuminate\Support\Carbon::parse($article->published_at)->toDateString() }}">{{ \Illuminate\Support\Carbon::parse($article->published_at)->translatedFormat('d F Y') }}</time>
                            <h2 class="mt-3 text-xl font-bold leading-snug"><a href="{{ route('articles.show', $article->slug) }}" class="hover:text-[#a54d25] focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#be6332]">{{ $article->title }}</a></h2>
                            @if ($article->excerpt)<p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ $article->excerpt }}</p>@endif
                            <a href="{{ route('articles.show', $article->slug) }}" class="mt-auto pt-5 text-sm font-semibold text-[#a54d25] underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#be6332]">Baca selengkapnya <span aria-hidden="true">→</span></a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-8">{{ $articles->links() }}</div>
        @endif
    </div>
@endsection
