@extends('layouts.admin')

@section('title', 'Artikel / News')
@section('section_label', 'Artikel / News')

@section('content')
    <x-admin.page-header title="Artikel / News" description="Kurasi artikel karier. Draf hanya muncul di panel ini; artikel terbit tersedia untuk pembaca publik.">
        <x-slot:actions><a href="{{ route('admin.articles.create') }}" class="admin-button-primary">Tulis artikel</a></x-slot:actions>
    </x-admin.page-header>
    <nav class="mb-6 flex gap-6 overflow-x-auto border-b border-slate-200" aria-label="Filter artikel">
        @foreach (['' => 'Semua', 'draft' => 'Draf', 'published' => 'Terbit'] as $value => $label)
            <a href="{{ route('admin.articles.index', $value ? ['status' => $value] : []) }}" @class(['shrink-0 border-b-2 pb-3 text-sm font-semibold', 'border-[#EE8A34] text-slate-900' => request('status', '') === $value, 'border-transparent text-slate-500' => request('status', '') !== $value])>{{ $label }}</a>
        @endforeach
    </nav>
    <p class="mb-3 text-sm text-slate-500">{{ number_format($articles->total(), 0, ',', '.') }} artikel ditemukan</p>
    @if ($articles->isEmpty())
        <div class="admin-panel"><x-admin.empty-state title="Belum ada artikel" description="Mulai dengan menulis artikel karier pertama." :href="route('admin.articles.create')" action="Tulis artikel" /></div>
    @else
        <x-admin.data-table>
            <x-slot:mobile>
                @foreach ($articles as $article)
                    <a href="{{ route('admin.articles.edit', $article->id) }}" class="block px-4 py-4"><span class="block font-semibold text-slate-900">{{ $article->title }}</span><span class="mt-1 flex items-center gap-2 text-xs text-slate-500"><x-admin.status-badge :status="$article->status" /> {{ \Illuminate\Support\Carbon::parse($article->created_at)->format('d M Y') }}</span></a>
                @endforeach
            </x-slot:mobile>
            <table class="admin-table"><thead><tr><th>Judul</th><th>Status</th><th>Dibuat</th><th class="text-right">Aksi</th></tr></thead><tbody>
                @foreach ($articles as $article)
                    <tr><td class="max-w-lg font-semibold text-slate-900">{{ $article->title }}</td><td><x-admin.status-badge :status="$article->status" /></td><td class="admin-number whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($article->created_at)->format('d M Y') }}</td><td class="whitespace-nowrap text-right"><a href="{{ route('admin.articles.edit', $article->id) }}" class="admin-link">Ubah</a><x-admin.confirm-dialog title="Hapus artikel?" :message="'Artikel “'.$article->title.'” akan dihapus permanen.'" :endpoint="url('/api/admin/articles/'.$article->id)" :success-url="route('admin.articles.index')" /></td></tr>
                @endforeach
            </tbody></table>
        </x-admin.data-table>
        <x-admin.pagination :paginator="$articles" />
    @endif
@endsection
