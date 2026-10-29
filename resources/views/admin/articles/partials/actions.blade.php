<div class="inline-flex items-center justify-end gap-1">
    <a href="{{ route('admin.articles.edit', $article->id) }}" class="admin-button-quiet w-11 text-slate-700 hover:text-[#183047]" aria-label="Ubah artikel {{ $article->title }}" title="Ubah artikel {{ $article->title }}">
        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M4 17.5V20h2.5L18.7 7.8a1.8 1.8 0 0 0-2.5-2.5L4 17.5Z" /></svg>
    </a>
    <x-admin.confirm-dialog title="Hapus artikel?" :message="'Artikel “'.$article->title.'” akan dihapus permanen.'" :endpoint="url('/api/admin/articles/'.$article->id)" :success-url="route('admin.articles.index')" :button-label="'Hapus artikel '.$article->title">
        <x-slot:trigger><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v7m4-7v7M6 7l1 14h10l1-14M9 7V4h6v3" /></svg></x-slot:trigger>
    </x-admin.confirm-dialog>
</div>
