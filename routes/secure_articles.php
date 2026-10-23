<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
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
        ]);
        $id = (string) Str::uuid();
        DB::table('articles')->insert([
            'id' => $id, 'author_id' => $request->user()->id,
            'title' => $input['title'], 'content' => $input['content'],
            'status' => $input['status'], 'cover_image' => $input['cover_image'] ?? null,
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
        ]);
        if (isset($input['status'])) {
            $input['published_at'] = $input['status'] === 'published'
                ? ($article->published_at ?? now()) : null;
        }
        $input['updated_at'] = now();
        DB::table('articles')->where('id', $id)->update($input);

        return response()->json(['data' => DB::table('articles')->where('id', $id)->first()]);
    });

    Route::delete('/articles/{id}', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'admin', 403);
        abort_unless(DB::table('articles')->where('id', $id)->delete(), 404);

        return response()->noContent();
    });
});
