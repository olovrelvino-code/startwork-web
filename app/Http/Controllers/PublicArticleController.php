<?php

namespace App\Http\Controllers;

use App\ArticleDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicArticleController extends Controller
{
    public function index(): View
    {
        $articles = DB::table('articles')
            ->where('status', 'published')->whereNotNull('slug')
            ->select('slug', 'title', 'excerpt', 'cover_image', 'published_at')
            ->orderByDesc('published_at')->orderByDesc('id')->paginate(9);
        $articles->getCollection()->transform(function (object $article): object {
            $article->cover_url = $this->coverPath($article->cover_image) ? route('articles.cover', $article->slug) : null;

            return $article;
        });

        return view('articles.index', compact('articles'));
    }

    public function show(string $slug): View
    {
        $article = DB::table('articles')->join('users', 'articles.author_id', '=', 'users.id')
            ->where('articles.slug', $slug)->where('articles.status', 'published')
            ->select('articles.id', 'articles.slug', 'articles.title', 'articles.excerpt', 'articles.content', 'articles.body_json', 'articles.cover_image', 'articles.published_at', 'users.full_name as author_name')
            ->first();
        abort_if($article === null, 404);
        $article->cover_url = $this->coverPath($article->cover_image) ? route('articles.cover', $slug) : null;
        $article->rendered_body = $article->body_json ? ArticleDocument::render($article->body_json, $slug) : null;

        return view('articles.show', compact('article'));
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
