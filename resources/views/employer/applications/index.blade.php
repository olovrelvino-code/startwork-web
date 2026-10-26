@extends('layouts.employer')

@section('title', 'Pelamar & CV')
@section('section_label', 'Pelamar & CV')

@section('content')
    <x-admin.page-header title="Pelamar & CV" :description="$applications->total().' lamaran untuk lowongan Anda.'" />

    <form method="GET" action="{{ route('employer.applications.index') }}" class="mb-5 flex flex-wrap items-end gap-3">
        <div class="min-w-[220px] flex-1 sm:max-w-sm"><label class="admin-label" for="job-filter">Lowongan</label><select class="admin-input" id="job-filter" name="job_id"><option value="">Semua lowongan</option>@foreach ($jobs as $job)<option value="{{ $job->id }}" @selected(request('job_id') === $job->id)>{{ $job->title }}</option>@endforeach</select></div>
        <button class="admin-button-secondary" type="submit">Terapkan</button>
    </form>

    <x-admin.data-table>
        <div class="md:hidden">
            @forelse ($applications as $application)
                <a class="block border-b border-slate-100 px-5 py-4 last:border-0" href="{{ route('employer.applications.show', $application->id) }}"><span class="flex items-start justify-between gap-3"><span class="font-semibold text-slate-900">{{ $application->full_name }}</span><x-admin.status-badge :status="$application->status" /></span><span class="mt-2 block text-sm text-slate-600">{{ $application->job_title }} · {{ $application->applied_at ? \Illuminate\Support\Carbon::parse($application->applied_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y') : 'Tanggal tidak tersedia' }}</span></a>
            @empty
                <x-admin.empty-state title="Belum ada lamaran" description="Lamaran akan muncul setelah ada lowongan aktif yang dilamar." />
            @endforelse
        </div>
        <table class="admin-table hidden md:table"><thead><tr><th>Pelamar</th><th>Lowongan</th><th>Status</th><th>Melamar</th></tr></thead><tbody>
            @forelse ($applications as $application)
                <tr><td><a class="admin-link" href="{{ route('employer.applications.show', $application->id) }}">{{ $application->full_name }}</a></td><td>{{ $application->job_title }}</td><td><x-admin.status-badge :status="$application->status" /></td><td>{{ $application->applied_at ? \Illuminate\Support\Carbon::parse($application->applied_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') : '—' }}</td></tr>
            @empty
                <tr><td colspan="4"><x-admin.empty-state title="Belum ada lamaran" description="Lamaran untuk lowongan Anda akan tampil di sini." /></td></tr>
            @endforelse
        </tbody></table>
    </x-admin.data-table>
    <x-admin.pagination :paginator="$applications" />
@endsection
