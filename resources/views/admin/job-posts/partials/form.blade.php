@php($editing = isset($job))
<form data-admin-form data-method="{{ $editing ? 'PUT' : 'POST' }}" data-success-url="{{ $editing ? route('admin.job-posts.show', $job->id) : url('/admin/job-posts/{id}') }}" data-success-message="Lowongan berhasil {{ $editing ? 'diperbarui' : 'diterbitkan' }}." action="{{ $editing ? url('/api/admin/job-posts/'.$job->id) : url('/api/admin/job-posts') }}" class="space-y-7">
    @csrf
    <section class="admin-panel p-5 sm:p-6" aria-labelledby="job-basic-title">
        <h2 id="job-basic-title" class="mb-5 text-lg font-semibold text-slate-900">Informasi lowongan</h2>
        <div class="grid gap-5 sm:grid-cols-2">
            @if (! $editing)
                <div class="sm:col-span-2">
                    <label class="admin-label" for="job-company">Perusahaan milik Anda</label>
                    <select class="admin-input" name="company_id" id="job-company" required>
                        <option value="">Pilih perusahaan</option>
                        @foreach ($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Lowongan admin hanya dapat dibuat untuk perusahaan milik akun ini.</p>
                </div>
            @else
                <div class="sm:col-span-2"><p class="admin-label">Perusahaan</p><p class="font-semibold text-slate-900">{{ $job->company_name }}</p></div>
            @endif
            <div class="sm:col-span-2">
                <label class="admin-label" for="job-title">Judul posisi</label>
                <input class="admin-input" id="job-title" name="title" value="{{ $job->title ?? '' }}" required maxlength="255">
            </div>
            <div><label class="admin-label" for="job-category">Kategori</label><input class="admin-input" id="job-category" name="category" value="{{ $job->category ?? '' }}" maxlength="255"></div>
            <div><label class="admin-label" for="job-location">Lokasi</label><input class="admin-input" id="job-location" name="location" value="{{ $job->location ?? '' }}" required maxlength="255"></div>
            <div><label class="admin-label" for="job-type">Tipe pekerjaan</label><select class="admin-input" id="job-type" name="job_type" required><option value="">Pilih tipe</option>@foreach (['Full-time', 'Part-time', 'Internship', 'Contract'] as $type)<option value="{{ $type }}" @selected(($job->job_type ?? '') === $type)>{{ $type }}</option>@endforeach</select></div>
            <div><label class="admin-label" for="work-type">Sistem kerja</label><select class="admin-input" id="work-type" name="work_type" required><option value="">Pilih sistem</option>@foreach (['On-site', 'Hybrid', 'Remote'] as $type)<option value="{{ $type }}" @selected(($job->work_type ?? '') === $type)>{{ $type }}</option>@endforeach</select></div>
            <div><label class="admin-label" for="salary-min">Gaji minimum (Rp)</label><input class="admin-input admin-number" id="salary-min" name="salary_min" type="number" min="0" step="1" value="{{ $job->salary_min ?? '' }}"></div>
            <div><label class="admin-label" for="salary-max">Gaji maksimum (Rp)</label><input class="admin-input admin-number" id="salary-max" name="salary_max" type="number" min="0" step="1" value="{{ $job->salary_max ?? '' }}"></div>
            <div><label class="admin-label" for="job-expires">Batas tayang (WIB)</label><input class="admin-input" id="job-expires" name="expires_at" type="datetime-local" value="{{ isset($job) && $job->expires_at ? \Illuminate\Support\Carbon::parse($job->expires_at, 'UTC')->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i') : '' }}"><p class="mt-1 text-xs text-slate-500">Isi tanggal di masa depan agar lowongan dapat diaktifkan kembali.</p></div>
            @if (! $editing || $job->poster_role === 'admin')
                <div><label class="admin-label" for="apply-url">Tautan pendaftaran eksternal</label><input class="admin-input" id="apply-url" name="apply_url" type="text" value="{{ $job->apply_url ?? '' }}" placeholder="https://… atau mailto:…"><p class="mt-1 text-xs text-slate-500">Kosongkan untuk menerima lamaran internal.</p></div>
            @endif
        </div>
    </section>

    <section class="admin-panel p-5 sm:p-6" aria-labelledby="job-content-title">
        <h2 id="job-content-title" class="mb-5 text-lg font-semibold text-slate-900">Isi lowongan</h2>
        <div class="space-y-5">
            @foreach ([
                ['description', 'Deskripsi pekerjaan'],
                ['responsibilities', 'Tanggung jawab'],
                ['requirements', 'Persyaratan'],
                ['skills', 'Keahlian'],
                ['benefits', 'Benefit'],
            ] as [$name, $label])
                <div><label class="admin-label" for="job-{{ $name }}">{{ $label }}</label><textarea class="admin-input min-h-28" id="job-{{ $name }}" name="{{ $name }}">{{ $job->{$name} ?? '' }}</textarea></div>
            @endforeach
        </div>
    </section>
    <div class="flex flex-wrap items-center gap-3"><button type="submit" class="admin-button-primary">{{ $editing ? 'Simpan perubahan' : 'Terbitkan lowongan' }}</button><a href="{{ $editing ? route('admin.job-posts.show', $job->id) : route('admin.job-posts.index') }}" class="admin-button-secondary">Batal</a></div>
</form>
