@extends('layouts.admin')

@section('title', 'Buat Lowongan')
@section('section_label', 'Lowongan Kerja / Baru')

@section('content')
    <x-admin.page-header title="Buat lowongan" :back="route('admin.job-posts.index')" description="Pilih perusahaan milik admin, lalu lengkapi informasi yang akan ditampilkan kepada pencari kerja." />
    @if ($companies->isEmpty())
        <div class="admin-panel"><x-admin.empty-state title="Buat perusahaan terlebih dahulu" description="Akun ini belum memiliki perusahaan untuk menerbitkan lowongan." :href="route('admin.companies.index')" action="Kelola perusahaan" /></div>
    @else
        @include('admin.job-posts.partials.form', ['companies' => $companies])
    @endif
@endsection
