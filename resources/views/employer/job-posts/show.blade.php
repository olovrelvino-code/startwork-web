@extends('layouts.employer')

@section('title', $job->title)
@section('section_label', 'Lowongan kerja')

@section('content')
    <x-admin.page-header :title="$job->title" :back="route('employer.job-posts.index')" :description="$job->company_name.' · '.$job->location">
        <x-slot:actions><x-admin.status-badge :status="$job->status" /><a class="admin-button-secondary" href="{{ route('employer.job-posts.edit', $job->id) }}">Ubah lowongan</a></x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-10 xl:grid-cols-[minmax(0,1.5fr)_minmax(280px,0.8fr)]">
        <div class="space-y-8">
            <section class="border-t border-slate-200 pt-5" aria-labelledby="job-detail-title">
                <h2 id="job-detail-title" class="mb-5 text-base font-semibold text-slate-900">Detail lowongan</h2>
                <dl class="grid gap-5 text-sm sm:grid-cols-2">
                    @foreach ([['Kategori', $job->category], ['Tipe pekerjaan', $job->job_type], ['Sistem kerja', $job->work_type], ['Lokasi', $job->location]] as [$label, $value])
                        <div><dt class="text-slate-500">{{ $label }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                    <div><dt class="text-slate-500">Gaji</dt><dd class="admin-number mt-1 font-medium text-slate-900">{{ $job->salary_min !== null ? 'Rp'.number_format($job->salary_min, 0, ',', '.') : '—' }}{{ $job->salary_max !== null ? ' – Rp'.number_format($job->salary_max, 0, ',', '.') : '' }}</dd></div>
                    <div><dt class="text-slate-500">Batas tayang</dt><dd class="mt-1 font-medium text-slate-900">{{ $job->expires_at ? \Illuminate\Support\Carbon::parse($job->expires_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : 'Belum diisi' }}</dd></div>
                </dl>
            </section>
            <section class="border-t border-slate-200 pt-5" aria-labelledby="job-description-title">
                <h2 id="job-description-title" class="mb-5 text-base font-semibold text-slate-900">Isi lowongan</h2>
                <div class="space-y-6 text-sm leading-relaxed text-slate-700">
                    @if (! $job->description && ! $job->responsibilities && ! $job->requirements && ! $job->skills && ! $job->benefits)
                        <p class="text-slate-500">Isi lowongan belum ditambahkan.</p>
                    @endif
                    @foreach ([['Deskripsi pekerjaan', $job->description], ['Tanggung jawab', $job->responsibilities], ['Persyaratan', $job->requirements], ['Keahlian', $job->skills], ['Benefit', $job->benefits]] as [$label, $value])
                        @if ($value)<div><h3 class="mb-2 font-semibold text-slate-900">{{ $label }}</h3><p class="whitespace-pre-line">{{ $value }}</p></div>@endif
                    @endforeach
                </div>
            </section>
        </div>
        <div class="space-y-8">
            <section class="border-t border-slate-200 pt-5" aria-labelledby="job-applications-title">
                <h2 id="job-applications-title" class="text-base font-semibold text-slate-900">Lamaran</h2>
                <p class="admin-number mt-2 text-2xl font-semibold text-slate-900">{{ number_format($applicationCount, 0, ',', '.') }}</p>
                <a class="admin-link mt-4 inline-block text-sm" href="{{ route('employer.applications.index', ['job_id' => $job->id]) }}">Lihat pelamar</a>
            </section>
            <section class="border-t border-slate-200 pt-5" aria-labelledby="job-status-title">
                <h2 id="job-status-title" class="mb-4 text-base font-semibold text-slate-900">Status lowongan</h2>
                @if ($job->status === 'active')
                    <form data-employer-form data-method="PUT" data-success-url="{{ route('employer.job-posts.show', $job->id) }}" action="{{ url('/api/employer/job-posts/'.$job->id.'/status') }}" class="space-y-4">
                        @csrf<input type="hidden" name="status" value="inactive">
                        <div><label class="admin-label" for="inactive-reason">Alasan menonaktifkan</label><input class="admin-input" id="inactive-reason" name="inactive_reason" required maxlength="255"></div>
                        <button type="submit" class="admin-button-secondary">Nonaktifkan lowongan</button>
                    </form>
                @else
                    @if ($subscription === null)
                        <p class="text-sm leading-relaxed text-slate-600">Lowongan belum diterbitkan. Hak publikasi belum aktif atau masa trial telah berakhir. Draft dan riwayat lowongan tetap tersimpan.</p>
                    @elseif (! $hasValidExpiry)
                        <p class="text-sm leading-relaxed text-slate-600">Batas tayang harus di masa depan dan tidak melewati akhir akses pada {{ \Illuminate\Support\Carbon::parse($subscription->expires_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB.</p>
                        <a href="{{ route('employer.job-posts.edit', $job->id) }}" class="admin-link mt-3 inline-block text-sm">Atur batas tayang</a>
                    @elseif (! $hasQuota)
                        <p class="text-sm leading-relaxed text-slate-600">Kuota lowongan aktif sudah penuh. Nonaktifkan lowongan lain sebelum menerbitkan draft ini.</p>
                    @else
                        <p class="mb-4 text-sm leading-relaxed text-slate-600">Akses publikasi aktif. Lowongan ini masih berupa draft dan belum terlihat oleh pencari kerja.</p>
                        <form data-employer-form data-method="PUT" data-success-url="{{ route('employer.job-posts.show', $job->id) }}" data-success-message="Lowongan berhasil diterbitkan." action="{{ url('/api/employer/job-posts/'.$job->id.'/status') }}">
                            @csrf<input type="hidden" name="status" value="active">
                            <button type="submit" class="admin-button-primary">Terbitkan lowongan</button>
                        </form>
                    @endif
                @endif
            </section>
            @if ($job->status !== 'active' && $applicationCount === 0)
                <section class="border-t border-slate-200 pt-5" aria-labelledby="job-delete-title">
                    <h2 id="job-delete-title" class="mb-3 text-base font-semibold text-slate-900">Hapus lowongan</h2>
                    <p class="mb-4 text-sm text-slate-600">Lowongan tanpa lamaran dapat dihapus selama tidak aktif.</p>
                    <form data-employer-form data-method="DELETE" data-confirm="Hapus lowongan ini secara permanen?" data-success-url="{{ route('employer.job-posts.index') }}" action="{{ url('/api/employer/job-posts/'.$job->id) }}">@csrf<button type="submit" class="admin-button-secondary text-rose-700">Hapus lowongan</button></form>
                </section>
            @endif
        </div>
    </div>
@endsection
