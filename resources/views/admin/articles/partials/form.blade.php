@php($editing = isset($article))
<form data-admin-form data-method="{{ $editing ? 'PUT' : 'POST' }}" enctype="multipart/form-data" data-success-url="{{ $editing ? route('admin.articles.index') : url('/admin/articles/{id}/edit') }}" data-success-message="Artikel berhasil {{ $editing ? 'diperbarui' : 'disimpan' }}." action="{{ $editing ? url('/api/admin/articles/'.$article->id) : url('/api/admin/articles') }}" class="space-y-6">
    @csrf
    <div class="admin-panel space-y-5 p-5 sm:p-6">
        <div><label class="admin-label" for="article-title">Judul artikel</label><input class="admin-input" id="article-title" name="title" required maxlength="255" value="{{ $article->title ?? '' }}"></div>
        <div><label class="admin-label" for="article-slug">Slug URL (opsional saat membuat)</label><input class="admin-input" id="article-slug" name="slug" maxlength="255" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="contoh: tips-mencari-kerja" value="{{ $article->slug ?? '' }}"><p class="mt-1 text-xs text-slate-500">Huruf kecil, angka, dan tanda hubung. Kosongkan agar dibuat otomatis.</p></div>
        <div><label class="admin-label" for="article-excerpt">Ringkasan (opsional)</label><textarea class="admin-input min-h-24" id="article-excerpt" name="excerpt" maxlength="500" placeholder="Ringkasan singkat untuk daftar artikel">{{ $article->excerpt ?? '' }}</textarea></div>
        <div>
            <label class="admin-label" for="article-content">Isi artikel</label>
            <textarea class="admin-input min-h-72" id="article-content" name="content" required>{{ $article->content ?? '' }}</textarea>
            <div class="hidden" data-article-editor data-article-id="{{ $article->id ?? '' }}" tabindex="-1">
                <input type="hidden" name="body_json" value="{{ $article->body_json ?? '' }}">
                <div class="article-editor-toolbar gap-2" role="toolbar" aria-label="Tambah blok artikel">
                    <button type="button" class="admin-button-secondary" data-add-block="paragraph">Paragraf</button>
                    <button type="button" class="admin-button-secondary" data-add-block="heading2">Heading 2</button>
                    <button type="button" class="admin-button-secondary" data-add-block="heading3">Heading 3</button>
                    <button type="button" class="admin-button-secondary" data-add-block="table">Tabel Perbandingan</button>
                    <button type="button" class="admin-button-secondary" data-add-block="image" @disabled(! $editing)>Gambar</button>
                </div>
                <p class="mt-2 text-xs text-slate-500">Pilih blok untuk menyisipkan setelah blok yang sedang diedit. Gunakan tombol panah untuk mengubah urutan. Judul artikel di atas menjadi H1.</p>
                @unless ($editing)<p class="mt-1 text-xs text-slate-500">Simpan draf terlebih dahulu untuk mengunggah gambar di tengah isi.</p>@endunless
                <p class="mt-2 text-sm text-slate-600" data-editor-status role="status" aria-live="polite"></p>
                <div class="mt-4 space-y-3" data-editor-blocks></div>
                <input type="file" class="hidden" data-editor-image-file accept=".jpg,.jpeg,.png" aria-label="Pilih gambar untuk isi artikel">
            </div>
            <p class="mt-1 text-xs text-slate-500" data-editor-fallback-help>Teks biasa. Editor blok memerlukan JavaScript.</p>
        </div>
        <x-admin.file-field name="cover_file" label="Gambar sampul (opsional)" />
        @if ($editing && $article->cover_image)<p class="text-xs text-slate-500">Sampul saat ini: {{ basename($article->cover_image) }}</p>@endif
        @if ($editing && $article->status === 'published' && $article->slug)<a class="admin-link" href="{{ route('articles.show', $article->slug) }}" target="_blank" rel="noopener">Lihat artikel terbit</a>@endif
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
