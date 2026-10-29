<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:draft,published']]);
        $articles = DB::table('articles')
            ->select('id', 'title', 'status', 'created_at', 'published_at', 'author_id')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(10)->withQueryString();

        return view('admin.articles.index', compact('articles'));
    }

    public function create(): View
    {
        return view('admin.articles.create');
    }

    public function edit(string $id): View
    {
        $article = DB::table('articles')->where('id', $id)->first();
        abort_if($article === null, 404);

        return view('admin.articles.edit', compact('article'));
    }
}
