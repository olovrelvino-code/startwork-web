@extends('layouts.public')

@section('title', $article->title)
@section('description', $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->content), 160))

@section('content')
    <article class="mx-auto max-w-4xl px-5 py-10 sm:px-8 sm:py-14">
        <a href="{{ route('articles.index') }}" class="text-sm font-semibold text-[var(--jobseeker-primary)] underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)]">← Kembali ke artikel</a>
        <header class="mt-8 border-b border-slate-200 pb-8">
            <time class="text-xs font-bold uppercase tracking-wider text-[var(--jobseeker-primary)]" datetime="{{ \Illuminate\Support\Carbon::parse($article->published_at)->toDateString() }}">{{ \Illuminate\Support\Carbon::parse($article->published_at)->translatedFormat('d F Y') }}</time>
            <h1 class="mt-3 break-words text-3xl font-bold leading-tight tracking-tight sm:text-5xl">{{ $article->title }}</h1>

            @php
                $hasHeadings = !empty($headings);
            @endphp
            <nav
                x-data="{
                    open: true,
                    headings: {{ Js::from($headings ?? []) }},
                    init() {
                        this.$nextTick(() => {
                            const container = document.querySelector('[data-article-content]');
                            if (!container) return;
                            const h2Elements = container.querySelectorAll('h2');
                            if (this.headings.length === 0 && h2Elements.length > 0) {
                                this.headings = Array.from(h2Elements).map((h2, idx) => {
                                    const slug = h2.textContent
                                        .toLowerCase()
                                        .trim()
                                        .replace(/[^\w\s-]/g, '')
                                        .replace(/[\s_-]+/g, '-')
                                        .replace(/^-+|-+$/g, '');
                                    return {
                                        id: slug ? 'section-' + slug : 'section-' + (idx + 1),
                                        text: h2.textContent.trim(),
                                    };
                                });
                            }
                            h2Elements.forEach((h2, idx) => {
                                if (this.headings && this.headings[idx]) {
                                    h2.id = this.headings[idx].id;
                                }
                            });
                        });
                    }
                }"
                x-show="headings && headings.length > 0"
                @if(!$hasHeadings) style="display: none;" @endif
                class="mt-6 rounded-xl border border-slate-200 bg-white p-5 sm:p-6"
                aria-label="Daftar Isi"
            >
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[var(--jobseeker-primary)]">
                        <svg class="h-4 w-4 text-[var(--jobseeker-accent)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="8" y1="6" x2="21" y2="6"></line>
                            <line x1="8" y1="12" x2="21" y2="12"></line>
                            <line x1="8" y1="18" x2="21" y2="18"></line>
                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                        </svg>
                        <span>Daftar Isi</span>
                    </h2>
                    <button
                        type="button"
                        @click="open = !open"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)]"
                        :aria-expanded="open.toString()"
                        :aria-label="open ? 'Tutup daftar isi' : 'Buka daftar isi'"
                        :title="open ? 'Tutup daftar isi' : 'Buka daftar isi'"
                    >
                        <svg aria-hidden="true" class="h-5 w-5 transition-transform duration-200 motion-reduce:transition-none" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
                    </button>
                </div>
                <ol x-show="open" class="-mx-5 mt-3.5 space-y-1 text-sm text-slate-700 sm:-mx-6">
                    <template x-for="(item, idx) in headings" :key="item.id || idx">
                        <li>
                            <a
                                :href="'#' + item.id"
                                class="group flex w-full items-start gap-3 px-5 py-2.5 text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-100 hover:text-[var(--jobseeker-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)] motion-reduce:transform-none motion-reduce:transition-none sm:px-6"
                            >
                                <span class="shrink-0 font-semibold text-slate-400 transition-colors group-hover:text-[var(--jobseeker-accent)]" x-text="(idx + 1) + '.'"></span>
                                <span class="flex-1 leading-relaxed underline-offset-4 group-hover:underline" x-text="item.text"></span>
                                <span class="shrink-0 text-slate-300 opacity-0 transition-opacity group-hover:opacity-100 group-hover:text-[var(--jobseeker-accent)]" aria-hidden="true">→</span>
                            </a>
                        </li>
                    </template>
                    @if ($hasHeadings)
                        @foreach ($headings as $index => $item)
                            <li x-show="false">
                                <a
                                    href="#{{ $item['id'] }}"
                                    class="group flex w-full items-start gap-3 px-5 py-2.5 text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-100 hover:text-[var(--jobseeker-primary)] motion-reduce:transform-none motion-reduce:transition-none sm:px-6"
                                >
                                    <span class="shrink-0 font-semibold text-slate-400 group-hover:text-[var(--jobseeker-accent)]">{{ $index + 1 }}.</span>
                                    <span class="flex-1 leading-relaxed underline-offset-4 group-hover:underline">{{ $item['text'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ol>
            </nav>

            @if ($article->excerpt)<p class="mt-5 text-lg leading-8 text-slate-600">{{ $article->excerpt }}</p>@endif
            <p class="mt-5 text-sm text-slate-500">Oleh {{ $article->author_name }}</p>
        </header>

        @if ($article->cover_url)<img src="{{ $article->cover_url }}" alt="Sampul artikel {{ $article->title }}" width="1200" height="700" class="mt-8 aspect-[12/7] w-full rounded-xl object-cover">@endif

        @if ($article->rendered_body)
            <div data-article-content class="article-body mt-9">{{ $article->rendered_body }}</div>
        @else
            <div data-article-content class="article-body mt-9 whitespace-pre-line break-words text-base leading-8 text-[var(--jobseeker-primary-dark)] sm:text-lg">{!! \App\ArticleDocument::renderInlineLinks(nl2br(e($article->content))) !!}</div>
        @endif

        {{-- 3. Detail Artikel — FAQ Accordion --}}
        @if (!empty($faqs))
            <section class="mt-14 border-t border-slate-200 pt-10" aria-labelledby="article-faq-heading">
                <div class="mb-6">
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-[var(--jobseeker-primary)]">Tanya Jawab</p>
                    <h2 id="article-faq-heading" class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Pertanyaan Terkait Artikel</h2>
                    <p class="mt-2 text-sm text-slate-600">Informasi dan panduan seputar wawasan karier serta peluang kerja di StartWork.</p>
                </div>
                <x-accordion :items="$faqs" name="article-faq" />
            </section>
        @endif

        {{-- 4. Artikel Terbaru --}}
        @if (!empty($latestArticles) && $latestArticles->isNotEmpty())
            <section class="mt-14 border-t border-slate-200 pt-10" aria-labelledby="latest-articles-heading">
                <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.18em] text-[var(--jobseeker-primary)]">Artikel Terbaru</p>
                        <h2 id="latest-articles-heading" class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Baca Wawasan Lainnya</h2>
                    </div>
                    <a href="{{ route('articles.index') }}" class="text-sm font-semibold text-[var(--jobseeker-primary)] underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)]">
                        Lihat semua artikel <span aria-hidden="true">→</span>
                    </a>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($latestArticles as $latest)
                        <article class="jobseeker-article-card group flex min-w-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white">
                            @if ($latest->cover_url)
                                <a href="{{ route('articles.show', $latest->slug) }}" tabindex="-1" aria-hidden="true" class="overflow-hidden">
                                    <img src="{{ $latest->cover_url }}" alt="" loading="lazy" width="720" height="420" class="jobseeker-card-image aspect-[12/7] w-full object-cover">
                                </a>
                            @else
                                <a href="{{ route('articles.show', $latest->slug) }}" tabindex="-1" aria-hidden="true" class="grid aspect-[12/7] w-full place-items-center bg-slate-100 text-slate-400">
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" /><path d="m21 15-5-5L5 21" /></svg>
                                </a>
                            @endif
                            <div class="flex flex-1 flex-col p-4 sm:p-5">
                                <time class="text-xs font-semibold uppercase tracking-wider text-slate-500" datetime="{{ \Illuminate\Support\Carbon::parse($latest->published_at)->toDateString() }}">
                                    {{ \Illuminate\Support\Carbon::parse($latest->published_at)->translatedFormat('d F Y') }}
                                </time>
                                <h3 class="mt-2.5 text-base font-bold leading-snug">
                                    <a href="{{ route('articles.show', $latest->slug) }}" class="text-slate-900 hover:text-[var(--jobseeker-primary)] focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)]">
                                        {{ $latest->title }}
                                    </a>
                                </h3>
                                @if ($latest->excerpt)
                                    <p class="mt-2 line-clamp-2 text-xs leading-5 text-slate-600">{{ $latest->excerpt }}</p>
                                @endif
                                <a href="{{ route('articles.show', $latest->slug) }}" class="mt-auto pt-4 text-xs font-semibold text-[var(--jobseeker-primary)] underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)]">
                                    Baca selengkapnya <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="mt-12 border-t border-slate-200 pt-7">
            <a href="{{ route('articles.index') }}" class="text-sm font-semibold text-[var(--jobseeker-primary)] underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)]">Lihat artikel lainnya →</a>
        </div>
    </article>
@endsection
