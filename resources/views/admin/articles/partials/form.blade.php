@php($editing = isset($article))
<form data-admin-form data-method="{{ $editing ? 'PUT' : 'POST' }}" enctype="multipart/form-data" data-success-url="{{ route('admin.articles.index') }}" data-success-message="Artikel berhasil {{ $editing ? 'diperbarui' : 'disimpan' }}." action="{{ $editing ? url('/api/admin/articles/'.$article->id) : url('/api/admin/articles') }}" class="space-y-6">
    @csrf
    <div class="admin-panel space-y-5 p-5 sm:p-6">
        <div><label class="admin-label" for="article-title">Judul artikel</label><input class="admin-input" id="article-title" name="title" required maxlength="255" value="{{ $article->title ?? '' }}"></div>
        <div><label class="admin-label" for="article-content">Isi artikel</label><textarea class="admin-input min-h-72" id="article-content" name="content" required>{{ $article->content ?? '' }}</textarea><p class="mt-1 text-xs text-slate-500">Tulis konten teks atau Markdown. Pratinjau publik tersedia setelah artikel diterbitkan.</p></div>
        <x-admin.file-field name="cover_file" label="Gambar sampul (opsional)" />
        @if ($editing && $article->cover_image)<p class="text-xs text-slate-500">Sampul saat ini: {{ basename($article->cover_image) }}</p>@endif
        <fieldset>
            <legend class="admin-label">Status publikasi</legend>
            <div class="flex flex-wrap gap-5 text-sm text-slate-800">
                <label class="inline-flex items-center gap-2"><input type="radio" name="status" value="draft" class="text-[#EE8A34] focus:ring-[#EE8A34]" @checked(($article->status ?? 'draft') === 'draft')>Simpan sebagai draf</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="status" value="published" class="text-[#EE8A34] focus:ring-[#EE8A34]" @checked(($article->status ?? '') === 'published')>Terbitkan</label>
            </div>
        </fieldset>
    </div>
    <div class="flex flex-wrap gap-3"><button type="submit" class="admin-button-primary">{{ $editing ? 'Simpan perubahan' : 'Simpan artikel' }}</button><a href="{{ route('admin.articles.index') }}" class="admin-button-secondary">Batal</a></div>
</form>
