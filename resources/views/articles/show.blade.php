@extends('layouts.public')

@section('title', $article->title)
@section('description', $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->content), 160))

@section('content')
    <article class="mx-auto max-w-3xl px-5 py-10 sm:px-8 sm:py-14">
        <a href="{{ route('articles.index') }}" class="text-sm font-semibold text-[#a54d25] underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#be6332]">← Kembali ke artikel</a>
        <header class="mt-8 border-b border-slate-200 pb-8">
            <time class="text-xs font-bold uppercase tracking-wider text-[#a54d25]" datetime="{{ \Illuminate\Support\Carbon::parse($article->published_at)->toDateString() }}">{{ \Illuminate\Support\Carbon::parse($article->published_at)->translatedFormat('d F Y') }}</time>
            <h1 class="mt-3 break-words text-3xl font-bold leading-tight tracking-tight sm:text-5xl">{{ $article->title }}</h1>
            @if ($article->excerpt)<p class="mt-5 text-lg leading-8 text-slate-600">{{ $article->excerpt }}</p>@endif
            <p class="mt-5 text-sm text-slate-500">Oleh {{ $article->author_name }}</p>
        </header>
        @if ($article->cover_url)<img src="{{ $article->cover_url }}" alt="Sampul artikel {{ $article->title }}" width="1200" height="700" class="mt-8 aspect-[12/7] w-full rounded-xl object-cover">@endif
        @if ($article->rendered_body)
            <div class="article-body mt-9">{{ $article->rendered_body }}</div>
        @else
            <div class="mt-9 whitespace-pre-line break-words text-base leading-8 text-[#263c53] sm:text-lg">{{ $article->content }}</div>
        @endif
        <div class="mt-12 border-t border-slate-200 pt-7"><a href="{{ route('articles.index') }}" class="text-sm font-semibold text-[#a54d25] underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#be6332]">Lihat artikel lainnya →</a></div>
    </article>
@endsection
