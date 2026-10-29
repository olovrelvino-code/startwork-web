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
                    <div><dt class="text-slate-500">Terdaftar</dt><dd class="mt-1 font-medium text-slate-900">{{ \Illuminate\Support\Carbon::parse($company->created_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</dd></div>
                    <div><dt class="text-slate-500">Lokasi</dt><dd class="mt-1 font-medium text-slate-900">{{ $company->location ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Website</dt><dd class="mt-1 break-all font-medium text-slate-900">@if ($company->website)<a class="admin-link" href="{{ $company->website }}" target="_blank" rel="noopener noreferrer">{{ $company->website }}</a>@else — @endif</dd></div>
                    <div class="sm:col-span-2"><dt class="text-slate-500">Deskripsi</dt><dd class="mt-1 whitespace-pre-line text-slate-900">{{ $company->description ?: 'Belum ada deskripsi.' }}</dd></div>
                </dl>
            </section>
            <section aria-labelledby="company-jobs-title">
                <div class="mb-3 flex items-baseline justify-between gap-3"><h2 id="company-jobs-title" class="text-lg font-semibold text-slate-900">Lowongan perusahaan</h2><span class="text-sm text-slate-500">{{ $jobs->total() }} lowongan</span></div>
                <div class="admin-panel admin-data-surface divide-y divide-slate-100">
                    @forelse ($jobs as $job)
                        <a href="{{ route('admin.job-posts.show', $job->id) }}" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-slate-50"><span class="admin-number w-7 shrink-0 text-sm text-slate-500">{{ $jobs->firstItem() + $loop->index }}.</span><span class="min-w-0 flex-1 font-medium text-slate-900">{{ $job->title }}</span><x-admin.status-badge :status="$job->status" /></a>
                    @empty
                        <x-admin.empty-state title="Belum ada lowongan" description="Lowongan dari perusahaan ini akan muncul di sini." />
                    @endforelse
                </div>
                <x-admin.pagination :paginator="$jobs" :round="true" />
            </section>
        </div>

        <div class="space-y-7">
            <section class="admin-panel p-5 sm:p-6" aria-labelledby="company-trial-title">
                <div class="flex flex-wrap items-center justify-between gap-2"><h2 id="company-trial-title" class="text-lg font-semibold text-slate-900">Trial perusahaan</h2><x-admin.company-flags :created-at="$company->created_at" :trial-expires-at="$trial?->expires_at" :owner-role="$company->owner_role" /></div>
                @if ($trial)
                    <p class="mt-3 text-sm text-slate-600">Mulai {{ \Illuminate\Support\Carbon::parse($trial->starts_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB · Berakhir {{ \Illuminate\Support\Carbon::parse($trial->expires_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p>
                @elseif ($canGrantTrial && $packages->isNotEmpty())
                    <p class="mt-3 text-sm text-slate-600">Trial dimulai saat diberikan dan berlaku selama 30 hari. Kuota mengikuti paket yang dipilih.</p>
                    <form data-admin-form data-success-url="{{ route('admin.companies.show', $company->id) }}" data-success-message="Trial 30 hari berhasil diberikan." method="POST" action="{{ url('/api/admin/companies/'.$company->id.'/grant-trial') }}" class="mt-5 space-y-4">
                        @csrf
                        <div><label class="admin-label" for="trial-package">Paket trial</label><select class="admin-input" id="trial-package" name="package_id" required><option value="">Pilih paket</option>@foreach ($packages as $package)<option value="{{ $package->id }}">{{ $package->name }} · {{ $package->quota }} lowongan</option>@endforeach</select></div>
                        <button type="submit" class="admin-button-primary">Berikan trial 30 hari</button>
                    </form>
                @else
                    <p class="mt-3 text-sm text-slate-600">Trial hanya dapat diberikan sekali kepada perusahaan Employer yang belum memiliki riwayat subscription.</p>
                @endif
            </section>
            @if ($canManage)
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
                @if ($canDelete)
                    <div><x-admin.confirm-dialog title="Hapus perusahaan?" message="Company ini akan dihapus permanen. Akun pemilik tetap ada." :endpoint="url('/api/admin/companies/'.$company->id)" :success-url="route('admin.companies.index')" button-label="Hapus perusahaan" /></div>
                @else
                    <x-admin.notice type="info" message="Perusahaan tidak dapat dihapus karena memiliki lowongan atau riwayat subscription. Riwayat tersebut tetap disimpan." />
                @endif
            @else
                <x-admin.notice type="info" message="Perusahaan milik admin lain hanya dapat dipantau." />
            @endif
        </div>
    </div>
@endsection
