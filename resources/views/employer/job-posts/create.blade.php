@extends('layouts.employer')

@section('title', 'Buat lowongan')
@section('section_label', 'Lowongan kerja')

@section('content')
    <x-admin.page-header title="Buat lowongan" :back="route('employer.job-posts.index')" :description="'Untuk '.$company->name.'. Simpan lowongan, lalu terbitkan dari halaman detail jika akses publikasi aktif.'" />
    @if ($subscription && ! $hasQuota)
        <p class="mb-6 border-l-2 border-[#B85B12] pl-4 text-sm text-slate-700">Akses publikasi aktif, tetapi kuota lowongan sedang penuh. Lowongan baru dapat disimpan sebagai draft.</p>
    @endif
    @include('employer.job-posts.partials.form')
@endsection
