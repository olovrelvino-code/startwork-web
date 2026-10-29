@extends('layouts.public')

@section('title', $job->title)
@section('description', \Illuminate\Support\Str::limit($job->description ?: $job->title, 160))

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">← Semua lowongan</a>
        <div class="mt-7 grid items-start gap-7 lg:grid-cols-[minmax(0,1fr)_300px]">
            <article class="min-w-0 rounded-xl border border-slate-200 bg-white p-6 sm:p-8">
                <header class="border-b border-slate-200 pb-7"><div class="flex items-start gap-4">@if ($job->company_logo)<img src="{{ url('/api/companies/'.$job->company_id.'/logo') }}" alt="Logo {{ $job->company_name }}" class="h-14 w-14 shrink-0 rounded-lg border border-slate-200 object-contain">@endif<div class="min-w-0"><h1 class="text-3xl font-bold leading-tight tracking-tight">{{ $job->title }}</h1><a href="{{ route('companies.show', $job->company_id) }}" class="mt-2 inline-block text-sm font-semibold text-blue-700 hover:underline">{{ $job->company_name }}</a><p class="mt-1 text-sm text-slate-600">{{ $job->location ?: 'Lokasi tidak dicantumkan' }}</p></div></div></header>
                @foreach ([['Deskripsi pekerjaan', $job->description], ['Tanggung jawab', $job->responsibilities], ['Persyaratan', $job->requirements], ['Keahlian', $job->skills], ['Benefit', $job->benefits]] as [$title, $body])
                    @if ($body)<section class="mt-8"><h2 class="text-xl font-semibold">{{ $title }}</h2><p class="mt-3 whitespace-pre-line break-words text-sm leading-7 text-slate-700">{{ $body }}</p></section>@endif
                @endforeach
            </article>
            <aside class="rounded-xl border border-slate-200 bg-white p-6 lg:sticky lg:top-5">
                @if ($job->apply_url)
                    <a href="{{ url('/api/job-posts/'.$job->id.'/apply-now') }}" rel="nofollow" class="block rounded-lg bg-blue-600 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Apply Sekarang di situs perusahaan ↗</a>
                    <p class="mt-3 text-xs leading-5 text-slate-500">Pendaftaran dilanjutkan di luar StartWork.</p>
                @else
                @guest
                    <a href="{{ route('jobs.apply-continue', $job->id) }}" class="block rounded-lg bg-blue-600 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Masuk untuk melamar</a>
                @else
                    @if (auth()->user()->role === 'jobseeker')
                        <button type="button" data-job-apply="{{ $job->id }}" class="w-full rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:cursor-not-allowed disabled:bg-slate-300">Kirim lamaran</button>
                        <p data-apply-feedback class="mt-3 text-sm text-slate-600" role="status">Profil utama, minimal satu skill, dan CV diperlukan untuk melamar.</p>
                    @else
                        <p class="text-sm text-slate-600">Hanya akun Jobseeker yang dapat mengirim lamaran internal.</p>
                    @endif
                @endguest
                @endif
                <dl class="mt-7 space-y-4 border-t border-slate-200 pt-6 text-sm"><div><dt class="text-slate-500">Tipe pekerjaan</dt><dd class="mt-1 font-semibold">{{ $job->job_type ?: 'Tidak dicantumkan' }}</dd></div><div><dt class="text-slate-500">Sistem kerja</dt><dd class="mt-1 font-semibold">{{ $job->work_type ?: 'Tidak dicantumkan' }}</dd></div><div><dt class="text-slate-500">Kategori</dt><dd class="mt-1 font-semibold">{{ $job->category ?: 'Tidak dicantumkan' }}</dd></div><div><dt class="text-slate-500">Gaji</dt><dd class="mt-1 font-semibold">@if ($job->salary_min || $job->salary_max)Rp {{ number_format($job->salary_min ?? 0, 0, ',', '.') }} – Rp {{ number_format($job->salary_max ?? 0, 0, ',', '.') }}@else Tidak dicantumkan @endif</dd></div></dl>
            </aside>
        </div>
    </div>
@endsection
