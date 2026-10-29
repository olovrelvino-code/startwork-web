<?php

use App\ArticleDocument;
use App\WebpImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

Route::middleware(['web', 'auth'])->prefix('admin')->group(function (): void {
    Route::get('/articles', function (Request $request) {
        abort_unless($request->user()->role === 'admin', 403);
        $articles = DB::table('articles')->orderByDesc('created_at')->get();

        return response()->json(['total' => $articles->count(), 'data' => $articles]);
    });

    Route::post('/articles', function (Request $request) {
        abort_unless($request->user()->role === 'admin', 403);
        $input = $request->validate([
            'author_id' => ['missing'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required_without:body_json', 'nullable', 'string'],
            'body_json' => ['nullable', 'string', 'max:100000'],
            'status' => ['required', 'in:draft,published'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', Rule::unique('articles', 'slug')],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'string', 'max:255'],
            'cover_file' => ['nullable', ...WebpImage::rules()],
        ]);
        $id = (string) Str::uuid();
        $blocks = isset($input['body_json']) ? ArticleDocument::validate($input['body_json'], $id) : null;
        $content = $blocks !== null ? ArticleDocument::plainText($blocks) : $input['content'];
        if ($input['status'] === 'published' && trim($content) === '') {
            throw ValidationException::withMessages(['content' => 'Isi artikel wajib diisi sebelum diterbitkan.']);
        }
        $base = trim(Str::substr(Str::slug($input['title']), 0, 210), '-') ?: 'artikel';
        $coverImage = isset($input['cover_file'])
            ? WebpImage::store($input['cover_file'], 'article-covers', 'cover_file', 'local')
            : ($input['cover_image'] ?? null);
        try {
            DB::table('articles')->insert([
                'id' => $id, 'author_id' => $request->user()->id,
                'title' => $input['title'], 'slug' => $input['slug'] ?? $base.'-'.$id,
                'excerpt' => $input['excerpt'] ?? Str::limit(trim(strip_tags($content)), 180, ''),
                'content' => $content,
                'body_json' => $blocks === null ? null : json_encode(['blocks' => $blocks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => $input['status'], 'cover_image' => $coverImage,
                'published_at' => $input['status'] === 'published' ? now() : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            if (isset($input['cover_file'])) {
                Storage::disk('local')->delete($coverImage);
            }
            throw $exception;
        }

        return response()->json(['data' => DB::table('articles')->where('id', $id)->first()], 201);
    });

    Route::put('/articles/{id}', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'admin', 403);
        $article = DB::table('articles')->where('id', $id)->first();
        abort_if($article === null, 404);
        $input = $request->validate([
            'author_id' => ['missing'], 'id' => ['missing'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => $request->filled('body_json') ? ['sometimes', 'nullable', 'string'] : ['sometimes', 'required', 'string'],
            'body_json' => ['sometimes', 'required', 'string', 'max:100000'],
            'status' => ['sometimes', 'required', 'in:draft,published'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', Rule::unique('articles', 'slug')->ignore($id)],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:500'],
            'cover_image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cover_file' => ['sometimes', ...WebpImage::rules()],
        ]);
        if (isset($input['body_json'])) {
            $blocks = ArticleDocument::validate($input['body_json'], $id);
            $input['content'] = ArticleDocument::plainText($blocks);
            $input['body_json'] = json_encode(['blocks' => $blocks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (array_key_exists('content', $input)) {
            $input['body_json'] = null;
        }
        if (($input['status'] ?? $article->status) === 'published'
            && trim($input['content'] ?? $article->content ?? '') === '') {
            throw ValidationException::withMessages(['content' => 'Isi artikel wajib diisi sebelum diterbitkan.']);
        }
        $oldCoverImage = $article->cover_image;
        $newCoverImage = null;
        if (isset($input['cover_file'])) {
            $newCoverImage = WebpImage::store($input['cover_file'], 'article-covers', 'cover_file', 'local');
            $input['cover_image'] = $newCoverImage;
            unset($input['cover_file']);
        }
        if (isset($input['status'])) {
            $input['published_at'] = $input['status'] === 'published'
                ? ($article->published_at ?? now()) : null;
        }
        $input['updated_at'] = now();
        try {
            $updated = DB::table('articles')->where('id', $id)->update($input);
            if ($newCoverImage) {
                abort_unless($updated === 1, 404);
            }
        } catch (Throwable $exception) {
            if ($newCoverImage) {
                Storage::disk('local')->delete($newCoverImage);
            }
            throw $exception;
        }
        if ($oldCoverImage && array_key_exists('cover_image', $input) && $oldCoverImage !== $input['cover_image']
            && str_starts_with($oldCoverImage, 'article-covers/')
            && ! DB::table('articles')->where('cover_image', $oldCoverImage)->exists()) {
            Storage::disk('local')->delete($oldCoverImage);
            Storage::disk('public')->delete($oldCoverImage);
        }
        if (array_key_exists('body_json', $input)) {
            $newImages = ArticleDocument::imageNames($input['body_json']);
            foreach (Storage::disk('local')->files("article-media/{$id}") as $file) {
                if (! in_array(basename($file), $newImages, true)) {
                    Storage::disk('local')->delete($file);
                }
            }
        }

        return response()->json(['data' => DB::table('articles')->where('id', $id)->first()]);
    });

    Route::delete('/articles/{id}', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'admin', 403);
        $coverImage = DB::table('articles')->where('id', $id)->value('cover_image');
        abort_unless(DB::table('articles')->where('id', $id)->delete(), 404);
        if ($coverImage && str_starts_with($coverImage, 'article-covers/')
            && ! DB::table('articles')->where('cover_image', $coverImage)->exists()) {
            Storage::disk('local')->delete($coverImage);
            Storage::disk('public')->delete($coverImage);
        }
        Storage::disk('local')->deleteDirectory("article-media/{$id}");

        return response()->noContent();
    });

    Route::post('/articles/{id}/images', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'admin', 403);
        abort_unless(DB::table('articles')->where('id', $id)->exists(), 404);
        abort_if(count(Storage::disk('local')->files("article-media/{$id}")) >= 100, 422, 'Batas gambar artikel tercapai.');
        $input = $request->validate(['image' => ['required', ...WebpImage::rules()]]);
        $path = WebpImage::store($input['image'], "article-media/{$id}", 'image', 'local');
        $file = basename($path);

        return response()->json([
            'file' => $file,
            'url' => url("/api/admin/articles/{$id}/images/{$file}"),
        ], 201);
    })->middleware('throttle:30,1');

    Route::get('/articles/{id}/images/{image}', function (Request $request, string $id, string $image) {
        abort_unless($request->user()->role === 'admin', 403);
        abort_unless(DB::table('articles')->where('id', $id)->exists(), 404);
        abort_unless(preg_match('/\A[0-9a-f-]{36}\.webp\z/i', $image), 404);
        $path = "article-media/{$id}/{$image}";
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    });
});
