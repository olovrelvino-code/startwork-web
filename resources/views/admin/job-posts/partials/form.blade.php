@php($editing = isset($job))
<form data-admin-form data-method="{{ $editing ? 'PUT' : 'POST' }}" data-success-url="{{ $editing ? route('admin.job-posts.show', $job->id) : url('/admin/job-posts/{id}') }}" data-success-message="Lowongan berhasil {{ $editing ? 'diperbarui' : 'diterbitkan' }}." action="{{ $editing ? url('/api/admin/job-posts/'.$job->id) : url('/api/admin/job-posts') }}" class="space-y-7">
    @csrf
    <section class="admin-panel p-5 sm:p-6" aria-labelledby="job-basic-title">
        <h2 id="job-basic-title" class="mb-5 text-lg font-semibold text-slate-900">Informasi lowongan</h2>
        <div class="grid gap-5 sm:grid-cols-2">
            @if (! $editing)
                <div class="sm:col-span-2">
                    <label class="admin-label" for="job-company-search">Perusahaan milik Anda</label>
                    <input class="admin-input text-left" id="job-company-search" type="search" required autocomplete="off" placeholder="Ketik nama perusahaan" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="job-company-options" aria-describedby="job-company-hint" data-company-search="{{ route('admin.job-posts.company-options') }}" data-company-target="job-company">
                    <input type="hidden" name="company_id" id="job-company">
                    <div id="job-company-options" class="mt-2 hidden max-h-56 divide-y divide-slate-100 overflow-y-auto border-l-2 border-slate-200 pl-2 text-left" role="listbox"></div>
                    <p class="mt-1 text-xs text-slate-500" id="job-company-hint" role="status">Ketik nama, lalu pilih perusahaan milik akun ini dari hasil pencarian.</p>
                </div>
            @else
                <div class="sm:col-span-2"><p class="admin-label">Perusahaan</p><p class="font-semibold text-slate-900">{{ $job->company_name }}</p></div>
            @endif
            <div class="sm:col-span-2">
                <label class="admin-label" for="job-title">Judul posisi</label>
                <input class="admin-input" id="job-title" name="title" value="{{ $job->title ?? '' }}" required maxlength="255">
            </div>
            <div data-admin-category data-category-presets="{{ json_encode(\App\JobCategory::PREDEFINED) }}">
                <label class="admin-label" for="job-category-search">Kategori</label>
                <input type="hidden" name="category" value="{{ $job->category ?? '' }}" data-category-value>
                <div class="relative">
                    <div class="flex min-h-11 items-center gap-2 rounded-lg border border-slate-300 bg-white px-2 transition-colors focus-within:border-[#2B506B] focus-within:ring-1 focus-within:ring-[#2B506B]">
                        <div class="admin-category-chip hidden w-fit max-w-[55%] min-w-0 shrink-0 items-center gap-0.5" data-category-chip data-tone="legacy">
                            <span class="min-w-0 truncate pl-2" data-category-chip-label></span>
                            <button type="button" class="admin-category-chip-remove" data-category-remove aria-label="Hapus kategori {{ $job->category ?? 'terpilih' }}" title="Hapus kategori {{ $job->category ?? 'terpilih' }}">
                                <svg aria-hidden="true" focusable="false" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18" /></svg>
                            </button>
                        </div>
                        <input class="min-w-0 flex-1 border-0 bg-transparent px-1 py-2 text-sm text-slate-900 shadow-none placeholder:text-slate-400 focus:border-0 focus:outline-none focus:ring-0" id="job-category-search" type="search" autocomplete="off" placeholder="Cari kategori" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="job-category-options" aria-describedby="job-category-status" data-category-search>
                    </div>
                    <div id="job-category-options" class="absolute inset-x-0 top-full z-20 mt-1 hidden max-h-56 overflow-y-auto rounded-lg border border-slate-300 bg-white shadow-sm" role="listbox"></div>
                </div>
                <p id="job-category-status" class="mt-1 truncate text-xs text-slate-500" data-category-status role="status">Pilih satu kategori dari daftar.</p>
            </div>
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
