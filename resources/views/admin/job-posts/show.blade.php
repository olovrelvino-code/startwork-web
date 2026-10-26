@extends('layouts.admin')

@section('title', $job->title)
@section('section_label', 'Lowongan Kerja / Detail')

@section('content')
    <x-admin.page-header :title="$job->title" :back="route('admin.job-posts.index')" :description="$job->company_name.' · '.$job->location">
        <x-slot:actions>
            <x-admin.status-badge :status="$job->status" />
            @if ($isOwned || $isEmployerJob)<a href="{{ route('admin.job-posts.edit', $job->id) }}" class="admin-button-secondary">Ubah lowongan</a>@endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-7 xl:grid-cols-[minmax(0,1.5fr)_minmax(300px,1fr)]">
        <div class="space-y-7">
            <section class="admin-panel p-5 sm:p-6" aria-labelledby="job-detail-title">
                <h2 id="job-detail-title" class="mb-5 text-lg font-semibold text-slate-900">Rincian lowongan</h2>
                <dl class="grid gap-5 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Kategori</dt><dd class="mt-1 font-medium text-slate-900">{{ $job->category ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Sistem kerja</dt><dd class="mt-1 font-medium text-slate-900">{{ $job->work_type }} · {{ $job->job_type }}</dd></div>
                    <div><dt class="text-slate-500">Rentang gaji</dt><dd class="admin-number mt-1 font-medium text-slate-900">@if ($job->salary_min || $job->salary_max) Rp {{ number_format($job->salary_min ?? 0, 0, ',', '.') }} – {{ number_format($job->salary_max ?? 0, 0, ',', '.') }} @else Tidak dicantumkan @endif</dd></div>
                    <div><dt class="text-slate-500">Batas tayang (WIB)</dt><dd class="mt-1 font-medium text-slate-900">{{ $job->expires_at ? \Illuminate\Support\Carbon::parse($job->expires_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') : 'Tidak ditentukan' }}</dd></div>
                    <div><dt class="text-slate-500">Alur lamaran</dt><dd class="mt-1 font-medium text-slate-900">{{ $job->apply_url ? 'Eksternal' : 'Internal' }}</dd></div>
                    <div><dt class="text-slate-500">ID lowongan</dt><dd class="mt-1 break-all font-mono text-xs text-slate-900">{{ $job->id }}</dd></div>
                </dl>
                @foreach ([['Deskripsi', $job->description], ['Tanggung jawab', $job->responsibilities], ['Persyaratan', $job->requirements], ['Keahlian', $job->skills], ['Benefit', $job->benefits]] as [$heading, $text])
                    @if ($text)<div class="mt-6 border-t border-slate-100 pt-5"><h3 class="font-semibold text-slate-900">{{ $heading }}</h3><p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $text }}</p></div>@endif
                @endforeach
            </section>
            <section aria-labelledby="job-applicants-title">
                <div class="mb-3 flex items-baseline justify-between gap-3"><h2 id="job-applicants-title" class="text-lg font-semibold text-slate-900">Pelamar lowongan</h2><span class="text-sm text-slate-500">{{ $applicants->total() }} lamaran</span></div>
                <div class="admin-panel divide-y divide-slate-100">
                    @forelse ($applicants as $application)
                        <div class="flex items-center justify-between gap-3 px-5 py-4"><span class="font-medium text-slate-900">{{ $application->full_name }}</span><x-admin.status-badge :status="$application->status" /></div>
                    @empty
                        <x-admin.empty-state title="Belum ada pelamar" description="Lamaran internal yang masuk akan tampil di sini." />
                    @endforelse
                </div>
                <x-admin.pagination :paginator="$applicants" />
            </section>
        </div>

        <div class="space-y-7">
            @if ($isOwned || $isEmployerJob)
                <section class="admin-panel p-5 sm:p-6" aria-labelledby="job-status-title">
                    <h2 id="job-status-title" class="text-lg font-semibold text-slate-900">Status tayang</h2>
                    @if ($job->status === 'active')
                        <p class="mt-2 text-sm text-slate-600">Menutup lowongan memerlukan alasan yang jelas.</p>
                        <form data-admin-form data-method="PUT" data-success-url="{{ route('admin.job-posts.show', $job->id) }}" data-success-message="Lowongan dinonaktifkan." action="{{ url('/api/admin/job-posts/'.$job->id.'/status') }}" class="mt-5 space-y-4">
                            @csrf<input type="hidden" name="status" value="inactive">
                            <div><label class="admin-label" for="inactive-reason">Alasan penutupan</label><textarea class="admin-input min-h-24" id="inactive-reason" name="inactive_reason" required maxlength="255"></textarea></div>
                            <button type="submit" class="admin-button-secondary">Nonaktifkan lowongan</button>
                        </form>
                    @elseif ($isOwned)
                        <p class="mt-2 text-sm text-slate-600">{{ $job->inactive_reason ?: ($job->status === 'expired' ? 'Lowongan telah melewati batas tayang.' : 'Lowongan sedang tidak aktif.') }}</p>
                        @if ($job->expires_at && \Illuminate\Support\Carbon::parse($job->expires_at)->isFuture())
                            <form data-admin-form data-method="PUT" data-success-url="{{ route('admin.job-posts.show', $job->id) }}" data-success-message="Lowongan diaktifkan kembali." action="{{ url('/api/admin/job-posts/'.$job->id.'/status') }}" class="mt-5">@csrf<input type="hidden" name="status" value="active"><button type="submit" class="admin-button-primary">Aktifkan kembali</button></form>
                        @else
                            <p class="mt-4 text-sm text-slate-600">Perbarui batas tayang ke tanggal mendatang sebelum mengaktifkan kembali.</p>
                        @endif
                    @else
                        <p class="mt-2 text-sm text-slate-600">Aktivasi lowongan Employer dilakukan oleh Employer dengan hak akses subscription aktif.</p>
                    @endif
                </section>
                @if ($job->status !== 'active' && $applicants->total() === 0)
                    <x-admin.confirm-dialog title="Hapus lowongan?" message="Lowongan yang dihapus tidak dapat dipulihkan. Pastikan tidak ada riwayat pelamar." :endpoint="url('/api/admin/job-posts/'.$job->id)" :success-url="route('admin.job-posts.index')" button-label="Hapus lowongan" />
                @endif
            @else
                <x-admin.notice type="info" message="Lowongan admin lain tersedia untuk pemantauan saja." />
            @endif
        </div>
    </div>
@endsection
