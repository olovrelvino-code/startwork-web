@extends('layouts.admin')

@section('title', $company->name)
@section('section_label', 'Perusahaan / Detail')

@section('content')
    <x-admin.page-header :title="$company->name" :back="route('admin.companies.index')" description="Profil perusahaan dan seluruh lowongan yang terkait.">
        <x-slot:actions><x-admin.ownership-label :owner-id="$company->owner_user_id" :owner-role="$company->owner_role" /></x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-7 xl:grid-cols-[minmax(0,1.4fr)_minmax(300px,1fr)]">
        <div class="space-y-7">
            <section class="admin-panel p-5 sm:p-6" aria-labelledby="company-profile-title">
                <div class="mb-5 flex items-start gap-4">
                    @if ($company->logo)<img src="{{ url('/api/companies/'.$company->id.'/logo') }}" alt="Logo {{ $company->name }}" class="h-14 w-14 rounded-lg border border-slate-200 object-contain p-1">@endif
                    <div>
                        <h2 id="company-profile-title" class="text-lg font-semibold text-slate-900">Profil perusahaan</h2>
                        <p class="mt-1 text-sm text-slate-500">ID <span class="font-mono">{{ $company->id }}</span></p>
                    </div>
                </div>
                <dl class="grid gap-5 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Lokasi</dt><dd class="mt-1 font-medium text-slate-900">{{ $company->location ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Website</dt><dd class="mt-1 break-all font-medium text-slate-900">@if ($company->website)<a class="admin-link" href="{{ $company->website }}" target="_blank" rel="noopener noreferrer">{{ $company->website }}</a>@else — @endif</dd></div>
                    <div class="sm:col-span-2"><dt class="text-slate-500">Deskripsi</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $company->description ?: 'Belum ada deskripsi.' }}</dd></div>
                </dl>
            </section>
            <section aria-labelledby="company-jobs-title">
                <div class="mb-3 flex items-baseline justify-between gap-3"><h2 id="company-jobs-title" class="text-lg font-semibold text-slate-900">Lowongan perusahaan</h2><span class="text-sm text-slate-500">{{ $jobs->total() }} lowongan</span></div>
                <div class="admin-panel divide-y divide-slate-100">
                    @forelse ($jobs as $job)
                        <a href="{{ route('admin.job-posts.show', $job->id) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50"><span class="font-medium text-slate-900">{{ $job->title }}</span><x-admin.status-badge :status="$job->status" /></a>
                    @empty
                        <x-admin.empty-state title="Belum ada lowongan" description="Lowongan dari perusahaan ini akan muncul di sini." />
                    @endforelse
                </div>
                <x-admin.pagination :paginator="$jobs" />
            </section>
        </div>

        <div class="space-y-7">
            @if ($isOwned)
                <section class="admin-panel p-5 sm:p-6" aria-labelledby="edit-company-title">
                    <h2 id="edit-company-title" class="mb-5 text-lg font-semibold text-slate-900">Ubah profil</h2>
                    @include('admin.companies.partials.form', ['company' => $company])
                </section>
                <section class="admin-panel p-5 sm:p-6" aria-labelledby="logo-title">
                    <h2 id="logo-title" class="mb-4 text-lg font-semibold text-slate-900">Logo perusahaan</h2>
                    <form data-admin-form enctype="multipart/form-data" data-success-url="{{ route('admin.companies.show', $company->id) }}" data-success-message="Logo perusahaan berhasil diperbarui." method="POST" action="{{ url('/api/admin/companies/'.$company->id.'/logo') }}" class="space-y-4">
                        @csrf
                        <x-admin.file-field name="logo" label="Pilih gambar logo" required />
                        <button type="submit" class="admin-button-secondary">Unggah logo</button>
                    </form>
                </section>
                <div><x-admin.confirm-dialog title="Hapus perusahaan?" message="Perusahaan hanya dapat dihapus setelah seluruh lowongan dan riwayat langganannya kosong." :endpoint="url('/api/admin/companies/'.$company->id)" :success-url="route('admin.companies.index')" button-label="Hapus perusahaan" /></div>
            @else
                <x-admin.notice type="info" message="Perusahaan ini dapat dipantau, tetapi pengubahan dan penghapusan hanya tersedia bagi admin pemiliknya." />
            @endif
        </div>
    </div>
@endsection
