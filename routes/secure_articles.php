<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'cover_image' => ['nullable', 'string', 'max:255'],
            'cover_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:2048'],
        ]);
        $coverImage = isset($input['cover_file'])
            ? $input['cover_file']->store('article-covers', 'public')
            : ($input['cover_image'] ?? null);
        abort_if($coverImage === false, 500, 'Gagal menyimpan gambar sampul');
        $id = (string) Str::uuid();
        DB::table('articles')->insert([
            'id' => $id, 'author_id' => $request->user()->id,
            'title' => $input['title'], 'content' => $input['content'],
            'status' => $input['status'], 'cover_image' => $coverImage,
            'published_at' => $input['status'] === 'published' ? now() : null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['data' => DB::table('articles')->where('id', $id)->first()], 201);
    });

    Route::put('/articles/{id}', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'admin', 403);
        $article = DB::table('articles')->where('id', $id)->first();
        abort_if($article === null, 404);
        $input = $request->validate([
            'author_id' => ['missing'], 'id' => ['missing'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'status' => ['sometimes', 'required', 'in:draft,published'],
            'cover_image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cover_file' => ['sometimes', 'file', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:2048'],
        ]);
        $oldCoverImage = $article->cover_image;
        if (isset($input['cover_file'])) {
            $input['cover_image'] = $input['cover_file']->store('article-covers', 'public');
            abort_if($input['cover_image'] === false, 500, 'Gagal menyimpan gambar sampul');
            unset($input['cover_file']);
        }
        if (isset($input['status'])) {
            $input['published_at'] = $input['status'] === 'published'
                ? ($article->published_at ?? now()) : null;
        }
        $input['updated_at'] = now();
        DB::table('articles')->where('id', $id)->update($input);
        if ($oldCoverImage && array_key_exists('cover_image', $input) && $oldCoverImage !== $input['cover_image']
            && str_starts_with($oldCoverImage, 'article-covers/')
            && ! DB::table('articles')->where('cover_image', $oldCoverImage)->exists()) {
            Storage::disk('public')->delete($oldCoverImage);
        }

        return response()->json(['data' => DB::table('articles')->where('id', $id)->first()]);
    });

    Route::delete('/articles/{id}', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'admin', 403);
        $coverImage = DB::table('articles')->where('id', $id)->value('cover_image');
        abort_unless(DB::table('articles')->where('id', $id)->delete(), 404);
        if ($coverImage && str_starts_with($coverImage, 'article-covers/')
            && ! DB::table('articles')->where('cover_image', $coverImage)->exists()) {
            Storage::disk('public')->delete($coverImage);
        }

        return response()->noContent();
    });
});
