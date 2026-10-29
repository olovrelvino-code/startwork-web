@extends('layouts.employer')

@section('title', $application->full_name)
@section('section_label', 'Pelamar & CV')

@section('content')
    <x-admin.page-header :title="$application->full_name" :back="route('employer.applications.index')" :description="$application->job_title">
        <x-slot:actions><x-admin.status-badge :status="$application->status" /></x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-10 xl:grid-cols-[minmax(0,1.5fr)_minmax(280px,0.8fr)]">
        <section class="min-w-0 border-t border-slate-200 pt-5" aria-labelledby="applicant-profile-title">
            <h2 id="applicant-profile-title" class="text-base font-semibold text-slate-900">Profil pelamar</h2>
            <dl class="mt-4 grid gap-x-8 text-sm sm:grid-cols-2">
                @foreach ([['Domisili', $application->domicile], ['Tanggal lahir', $application->birth_date], ['Gender', $application->gender], ['Pendidikan terakhir', $application->last_education]] as [$label, $value])
                    <div class="border-b border-slate-100 py-3"><dt class="text-slate-500">{{ $label }}</dt><dd class="mt-1 font-medium text-slate-900">{{ $value ?: '—' }}</dd></div>
                @endforeach
                <div class="border-b border-slate-100 py-3"><dt class="text-slate-500">Melamar</dt><dd class="mt-1 font-medium text-slate-900">{{ $application->applied_at ? \Illuminate\Support\Carbon::parse($application->applied_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i').' WIB' : '—' }}</dd></div>
                <div class="border-b border-slate-100 py-3"><dt class="text-slate-500">Portfolio</dt><dd class="mt-1">@if ($application->portfolio_link && preg_match('/^https?:\/\//i', $application->portfolio_link))<a class="admin-link break-all" href="{{ $application->portfolio_link }}" target="_blank" rel="noopener noreferrer">Buka portfolio</a>@else — @endif</dd></div>
            </dl>
            @if ($application->has_cv)
                <a class="admin-button-secondary mt-6" data-employer-download href="{{ url('/api/employer/applications/'.$application->id.'/cv') }}">Unduh CV</a>
            @else
                <p class="mt-6 text-sm text-slate-500">CV tidak tersedia.</p>
            @endif
        </section>
        <div class="min-w-0 space-y-8">
            <section class="border-t border-slate-200 pt-5" aria-labelledby="work-experience-title">
                <h2 id="work-experience-title" class="text-base font-semibold text-slate-900">Work Experience</h2>
                @forelse ($workExperiences as $experience)
                    <div class="border-b border-slate-100 py-4 text-sm last:border-0">
                        <h3 class="font-semibold text-slate-900">{{ $experience->job_title ?: 'Posisi tidak diisi' }}</h3>
                        <p class="mt-1 text-slate-600">{{ $experience->company ?: 'Perusahaan tidak diisi' }}@if ($experience->job_type) · {{ $experience->job_type }}@endif</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $experience->start_date ?: 'Tanggal mulai tidak diisi' }} – {{ $experience->end_date ?: 'Tanggal akhir tidak diisi' }}</p>
                        @if ($experience->key_responsibilities)<p class="mt-2 whitespace-pre-line text-slate-700">{{ $experience->key_responsibilities }}</p>@endif
                    </div>
                @empty
                    <p class="mt-3 text-sm text-slate-500">Pengalaman kerja belum diisi.</p>
                @endforelse
            </section>
            <section class="border-t border-slate-200 pt-5" aria-labelledby="organization-title">
                <h2 id="organization-title" class="text-base font-semibold text-slate-900">Organization</h2>
                @forelse ($organizations as $organization)
                    <div class="border-b border-slate-100 py-4 text-sm last:border-0">
                        <h3 class="font-semibold text-slate-900">{{ $organization->organization_name ?: 'Organisasi tidak diisi' }}</h3>
                        <p class="mt-1 text-slate-600">{{ $organization->role ?: 'Peran tidak diisi' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $organization->start_date ?: 'Tanggal mulai tidak diisi' }} – {{ $organization->end_date ?: 'Tanggal akhir tidak diisi' }}</p>
                        @if ($organization->description)<p class="mt-2 whitespace-pre-line text-slate-700">{{ $organization->description }}</p>@endif
                    </div>
                @empty
                    <p class="mt-3 text-sm text-slate-500">Pengalaman organisasi belum diisi.</p>
                @endforelse
            </section>
            <section class="border-t border-slate-200 pt-5" aria-labelledby="skills-title">
                <h2 id="skills-title" class="text-base font-semibold text-slate-900">Skills</h2>
                @if ($skills->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">Keahlian belum diisi.</p>
                @else
                    <ul class="mt-3 flex flex-wrap gap-2">@foreach ($skills as $skill)<li class="rounded-md border border-slate-200 px-3 py-1 text-sm text-slate-700">{{ $skill->name }}</li>@endforeach</ul>
                @endif
            </section>
            <section class="border-t border-slate-200 pt-5" aria-labelledby="certificates-title">
                <h2 id="certificates-title" class="text-base font-semibold text-slate-900">Certificate</h2>
                @if ($certificates->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">Sertifikat belum diisi.</p>
                @else
                    <ul class="mt-3 list-inside list-disc space-y-2 text-sm text-slate-700">@foreach ($certificates as $certificate)<li>{{ $certificate->name ?: 'Nama sertifikat tidak diisi' }}</li>@endforeach</ul>
                @endif
            </section>
        </div>
        <section class="border-t border-slate-200 pt-5" aria-labelledby="application-status-title">
            <h2 id="application-status-title" class="mb-4 text-base font-semibold text-slate-900">Status lamaran</h2>
            @php($nextStatuses = match ($application->status) { 'submitted' => ['in_process' => 'Proses', 'accepted' => 'Terima', 'rejected' => 'Tolak'], 'in_process' => ['accepted' => 'Terima', 'rejected' => 'Tolak'], default => [] })
            @if ($nextStatuses)
                <p class="mb-4 text-sm text-slate-600">Perubahan status yang sudah disimpan tidak dapat dibatalkan.</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($nextStatuses as $status => $label)
                        <form data-employer-form data-method="PUT" data-confirm="Ubah status lamaran menjadi {{ strtolower($label) }}?" data-success-url="{{ route('employer.applications.show', $application->id) }}" action="{{ url('/api/employer/applications/'.$application->id.'/status') }}">
                            @csrf<input type="hidden" name="status" value="{{ $status }}"><button type="submit" class="admin-button-secondary">{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-600">Status akhir sudah tersimpan.</p>
            @endif
        </section>
    </div>
@endsection
