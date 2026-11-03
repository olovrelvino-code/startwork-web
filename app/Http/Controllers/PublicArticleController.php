<?php

namespace App\Http\Controllers;

use App\ArticleDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicArticleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $articles = DB::table('articles')
            ->where('status', 'published')->whereNotNull('slug')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->select('slug', 'title', 'excerpt', 'cover_image', 'published_at')
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(9)
            ->withQueryString();

        $articles->getCollection()->transform(function (object $article): object {
            $article->cover_url = $this->coverPath($article->cover_image) ? route('articles.cover', $article->slug) : null;

            return $article;
        });

        return view('articles.index', compact('articles', 'search'));
    }

    public function show(string $slug): View
    {
        $article = DB::table('articles')->join('users', 'articles.author_id', '=', 'users.id')
            ->where('articles.slug', $slug)->where('articles.status', 'published')
            ->select('articles.id', 'articles.slug', 'articles.title', 'articles.excerpt', 'articles.content', 'articles.body_json', 'articles.faq_json', 'articles.cover_image', 'articles.published_at', 'users.full_name as author_name')
            ->first();
        abort_if($article === null, 404);
        $article->cover_url = $this->coverPath($article->cover_image) ? route('articles.cover', $slug) : null;
        $article->rendered_body = $article->body_json ? ArticleDocument::render($article->body_json, $slug) : null;

        $headings = [];
        if ($article->body_json) {
            $document = json_decode($article->body_json, true);
            if (is_array($document['blocks'] ?? null)) {
                $headingIndex = 1;
                foreach ($document['blocks'] as $block) {
                    if (($block['type'] ?? null) === 'heading' && (int) ($block['level'] ?? 0) === 2) {
                        $text = trim((string) ($block['text'] ?? ''));
                        if ($text !== '') {
                            $slugText = Str::slug($text);
                            $headings[] = [
                                'id' => $slugText !== '' ? "section-{$slugText}" : "section-{$headingIndex}",
                                'text' => $text,
                            ];
                            $headingIndex++;
                        }
                    }
                }
            }
        } elseif ($article->content) {
            if (preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $article->content, $matches)) {
                foreach ($matches[1] as $idx => $headingText) {
                    $text = trim(strip_tags($headingText));
                    if ($text !== '') {
                        $slugText = Str::slug($text);
                        $headings[] = [
                            'id' => $slugText !== '' ? "section-{$slugText}" : 'section-'.($idx + 1),
                            'text' => $text,
                        ];
                    }
                }
            }
        }

        $faqs = [];
        if (! empty($article->faq_json)) {
            $rawFaqs = is_string($article->faq_json) ? json_decode($article->faq_json, true) : $article->faq_json;
            if (is_array($rawFaqs)) {
                foreach ($rawFaqs as $item) {
                    if (is_array($item)) {
                        $q = trim((string) ($item['question'] ?? ''));
                        $a = trim((string) ($item['answer'] ?? ''));
                        if ($q !== '' || $a !== '') {
                            $faqs[] = ['question' => $q, 'answer' => $a];
                        }
                    }
                }
            }
        }
        if (empty($faqs)) {
            $faqs = [
                [
                    'question' => 'Bagaimana cara memanfaatkan wawasan dalam artikel ini untuk mencari kerja?',
                    'answer' => 'Anda dapat menerapkan poin-poin persiapan yang dijelaskan, memperbarui profil dan CV di akun Jobseeker Anda, lalu menjelajahi lowongan yang sesuai di menu Lowongan StartWork.',
                ],
                [
                    'question' => 'Apakah lowongan yang terkait di StartWork terverifikasi?',
                    'answer' => 'Ya, seluruh lowongan yang diterbitkan di platform StartWork berasal dari perusahaan terverifikasi guna menjamin kredibilitas dan keamanan bagi para pencari kerja.',
                ],
                [
                    'question' => 'Apakah wawasan karier ini diperbarui secara berkala?',
                    'answer' => 'StartWork terus memperbarui artikel panduan, kebijakan ketenagakerjaan, serta tren rekrutmen terbaru untuk memberikan informasi terkini bagi Anda.',
                ],
                [
                    'question' => 'Dapatkah saya membagikan artikel ini ke media sosial atau jejaring profesional?',
                    'answer' => 'Tentu. Anda dapat menyalin tautan halaman ini untuk dibagikan ke LinkedIn, grup obrolan profesional, atau rekan kerja yang membutuhkan.',
                ],
            ];
        }

        $latestArticles = DB::table('articles')
            ->where('status', 'published')
            ->whereNotNull('slug')
            ->where('id', '!=', $article->id)
            ->select('id', 'slug', 'title', 'excerpt', 'cover_image', 'published_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        $latestArticles->transform(function (object $item): object {
            $item->cover_url = $this->coverPath($item->cover_image) ? route('articles.cover', $item->slug) : null;

            return $item;
        });

        return view('articles.show', compact('article', 'headings', 'faqs', 'latestArticles'));
    }

    public function image(string $slug, string $image): StreamedResponse
    {
        abort_unless(preg_match('/\A[0-9a-f-]{36}\.webp\z/i', $image), 404);
        $article = DB::table('articles')->where('slug', $slug)->where('status', 'published')
            ->first(['id', 'body_json']);
        abort_if($article === null || ! in_array($image, ArticleDocument::imageNames($article->body_json), true), 404);
        $path = "article-media/{$article->id}/{$image}";
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function cover(string $slug): StreamedResponse
    {
        $path = DB::table('articles')->where('slug', $slug)->where('status', 'published')->value('cover_image');
        $safePath = $this->coverPath($path);
        abort_if($safePath === null, 404);

        return Storage::disk('local')->exists($safePath)
            ? Storage::disk('local')->response($safePath)
            : Storage::disk('public')->response($safePath);
    }

    private function coverPath(?string $path): ?string
    {
        if (! $path || ! preg_match('/\Aarticle-covers\/[A-Za-z0-9][A-Za-z0-9._-]*\z/D', $path)) {
            return null;
        }

        return Storage::disk('local')->exists($path) || Storage::disk('public')->exists($path) ? $path : null;
    }
}
