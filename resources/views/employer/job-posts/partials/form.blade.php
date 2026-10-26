@php($editing = isset($job))
<form data-employer-form data-method="{{ $editing ? 'PUT' : 'POST' }}" data-success-url="{{ $editing ? route('employer.job-posts.show', $job->id) : url('/employer/job-posts/{id}') }}" data-success-message="{{ $editing ? 'Lowongan berhasil diperbarui.' : (($hasQuota ?? false) ? 'Draft tersimpan. Tinjau dan terbitkan lowongan pada halaman ini.' : 'Lowongan berhasil disimpan sebagai draft.') }}" action="{{ $editing ? url('/api/employer/job-posts/'.$job->id) : url('/api/employer/job-posts') }}" class="max-w-5xl space-y-8">
    @csrf
    <section class="border-t border-slate-200 pt-6" aria-labelledby="job-basic-title">
        <h2 id="job-basic-title" class="mb-5 text-base font-semibold text-slate-900">Posisi dan penempatan</h2>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="admin-label" for="job-title">Judul posisi</label><input class="admin-input" id="job-title" name="title" value="{{ $job->title ?? '' }}" required maxlength="255"></div>
            <div><label class="admin-label" for="job-category">Kategori</label><input class="admin-input" id="job-category" name="category" value="{{ $job->category ?? '' }}" maxlength="255"></div>
            <div><label class="admin-label" for="job-location">Lokasi</label><input class="admin-input" id="job-location" name="location" value="{{ $job->location ?? '' }}" required maxlength="255"></div>
            <div><label class="admin-label" for="job-type">Tipe pekerjaan</label><select class="admin-input" id="job-type" name="job_type" required><option value="">Pilih tipe</option>@foreach (['Full-time', 'Part-time', 'Internship', 'Contract'] as $type)<option value="{{ $type }}" @selected(($job->job_type ?? '') === $type)>{{ $type }}</option>@endforeach</select></div>
            <div><label class="admin-label" for="work-type">Sistem kerja</label><select class="admin-input" id="work-type" name="work_type" required><option value="">Pilih sistem</option>@foreach (['On-site', 'Hybrid', 'Remote'] as $type)<option value="{{ $type }}" @selected(($job->work_type ?? '') === $type)>{{ $type }}</option>@endforeach</select></div>
        </div>
    </section>

    <section class="border-t border-slate-200 pt-6" aria-labelledby="job-period-title">
        <h2 id="job-period-title" class="mb-5 text-base font-semibold text-slate-900">Gaji dan batas tayang</h2>
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="admin-label" for="salary-min">Gaji minimum (Rp)</label><input class="admin-input admin-number" id="salary-min" name="salary_min" type="number" min="0" step="1" value="{{ $job->salary_min ?? '' }}"></div>
            <div><label class="admin-label" for="salary-max">Gaji maksimum (Rp)</label><input class="admin-input admin-number" id="salary-max" name="salary_max" type="number" min="0" step="1" value="{{ $job->salary_max ?? '' }}"></div>
            <div class="sm:col-span-2"><label class="admin-label" for="job-expires">Batas tayang (WIB)</label><input class="admin-input max-w-sm" id="job-expires" name="expires_at" type="datetime-local" value="{{ isset($job) && $job->expires_at ? \Illuminate\Support\Carbon::parse($job->expires_at, 'UTC')->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i') : '' }}"><p class="mt-1 text-sm text-slate-500">@if ($subscription ?? null) Untuk menerbitkan, pilih tanggal di masa depan paling lambat {{ \Illuminate\Support\Carbon::parse($subscription->expires_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB. @else Tanggal di masa depan diperlukan sebelum aktivasi. @endif</p></div>
        </div>
    </section>

    <section class="border-t border-slate-200 pt-6" aria-labelledby="job-content-title">
        <h2 id="job-content-title" class="mb-5 text-base font-semibold text-slate-900">Isi lowongan</h2>
        <div class="space-y-5">
            @foreach ([['description', 'Deskripsi pekerjaan'], ['responsibilities', 'Tanggung jawab'], ['requirements', 'Persyaratan'], ['skills', 'Keahlian'], ['benefits', 'Benefit']] as [$name, $label])
                <div><label class="admin-label" for="job-{{ $name }}">{{ $label }}</label><textarea class="admin-input min-h-28" id="job-{{ $name }}" name="{{ $name }}">{{ $job->{$name} ?? '' }}</textarea></div>
            @endforeach
        </div>
    </section>

    <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-6"><button type="submit" class="admin-button-primary">{{ $editing ? 'Simpan perubahan' : (($hasQuota ?? false) ? 'Simpan dan lanjutkan' : 'Simpan draft') }}</button><a href="{{ $editing ? route('employer.job-posts.show', $job->id) : route('employer.job-posts.index') }}" class="admin-button-secondary">Batal</a></div>
</form>
