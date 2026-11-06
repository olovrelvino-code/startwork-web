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
                    <button type="button" class="admin-button-secondary" data-format="link">Hyperlink</button>
                </div>
                <p class="mt-2 text-xs text-slate-500">Pilih blok untuk menyisipkan setelah blok yang sedang diedit. Gunakan tombol panah untuk mengubah urutan. Gunakan tombol Hyperlink atau ikon Link pada blok untuk menyisipkan tautan web. Judul artikel di atas menjadi H1.</p>
                @unless ($editing)<p class="mt-1 text-xs text-slate-500">Simpan draf terlebih dahulu untuk mengunggah gambar di tengah isi.</p>@endunless
                <p class="mt-2 text-sm text-slate-600" data-editor-status role="status" aria-live="polite"></p>
                <div class="mt-4 space-y-3" data-editor-blocks></div>
                <input type="file" class="hidden" data-editor-image-file accept=".jpg,.jpeg,.png" aria-label="Pilih gambar untuk isi artikel">

                {{-- Dialog Hyperlink --}}
                <dialog data-link-dialog class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-5 shadow-2xl backdrop:bg-slate-900/40">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <h3 class="text-sm font-bold text-slate-900" data-link-dialog-title>Hyperlink</h3>
                            <button type="button" data-link-cancel class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕</button>
                        </div>
                        <div>
                            <label class="admin-label text-xs">Teks Tautan</label>
                            <input type="text" class="admin-input text-sm" data-link-text placeholder="Contoh: Baca selengkapnya" maxlength="255">
                        </div>
                        <div>
                            <label class="admin-label text-xs">URL Tautan</label>
                            <input type="url" class="admin-input text-sm" data-link-url placeholder="https://example.com" maxlength="500">
                            <p class="mt-1 text-xs text-slate-400">Gunakan tautan lengkap diawali https:// atau http://</p>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                            <button type="button" data-link-delete class="admin-button-quiet text-rose-600 hover:text-rose-700 text-xs font-semibold hidden">Hapus Link</button>
                            <div class="flex items-center gap-2 ml-auto">
                                <button type="button" data-link-cancel class="admin-button-secondary text-xs">Batal</button>
                                <button type="button" data-link-save class="admin-button-primary text-xs">Terapkan</button>
                            </div>
                        </div>
                    </div>
                </dialog>
            </div>
            <p class="mt-1 text-xs text-slate-500" data-editor-fallback-help>Teks biasa. Editor blok memerlukan JavaScript.</p>
        </div>

        <x-admin.file-field name="cover_file" label="Gambar sampul (opsional)" />
        @if ($editing && $article->cover_image)<p class="text-xs text-slate-500">Sampul saat ini: {{ basename($article->cover_image) }}</p>@endif
        @if ($editing && $article->status === 'published' && $article->slug)<a class="admin-link" href="{{ route('articles.show', $article->slug) }}" target="_blank" rel="noopener">Lihat artikel terbit</a>@endif

        {{-- 1. Blok FAQ Berbentuk Accordion --}}
        <div class="border-t border-slate-200 pt-5" data-article-faq-container>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="admin-label !mb-0 font-bold text-slate-900">FAQ Artikel (Accordion)</h3>
                    <p class="text-xs text-slate-500">Kelola daftar tanya-jawab khusus yang akan tampil pada bagian akhir artikel publik.</p>
                </div>
                <button type="button" class="admin-button-secondary text-xs" data-add-faq>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Tambah Tanya-Jawab
                </button>
            </div>

            <input type="hidden" name="faq_json" id="article-faq-json" value="{{ is_array($article->faq_json ?? null) ? json_encode($article->faq_json) : ($article->faq_json ?? '') }}">

            <div class="space-y-3" data-faq-list></div>
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 p-6 text-center text-sm text-slate-500 hidden" data-faq-empty>
                <p>Belum ada pertanyaan FAQ untuk artikel ini.</p>
                <button type="button" class="mt-2 text-xs font-semibold text-[var(--admin-brand)] hover:underline" data-add-faq-prompt>
                    + Tambah pertanyaan pertama
                </button>
            </div>
        </div>

        <fieldset>
            <legend class="admin-label">Status publikasi</legend>
            <div class="flex flex-wrap gap-5 text-sm text-slate-800">
                <label class="inline-flex items-center gap-2"><input type="radio" name="status" value="draft" class="text-[#DE7C23] focus:ring-[#DE7C23]" @checked(($article->status ?? 'draft') === 'draft')>Simpan sebagai draf</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="status" value="published" class="text-[#DE7C23] focus:ring-[#DE7C23]" @checked(($article->status ?? '') === 'published')>Terbitkan</label>
            </div>
        </fieldset>
    </div>
    <div class="flex flex-wrap gap-3"><button type="submit" class="admin-button-primary">{{ $editing ? 'Simpan perubahan' : 'Simpan artikel' }}</button><a href="{{ route('admin.articles.index') }}" class="admin-button-secondary">Batal</a></div>
</form>
