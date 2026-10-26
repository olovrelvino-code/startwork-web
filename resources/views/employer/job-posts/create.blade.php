@extends('layouts.employer')

@section('title', 'Buat lowongan')
@section('section_label', 'Lowongan kerja')

@section('content')
    <x-admin.page-header title="Buat lowongan" :back="route('employer.job-posts.index')" :description="'Untuk '.$company->name.'. Lowongan baru tersimpan sebagai inactive dan belum tampil untuk pencari kerja.'" />
    @include('employer.job-posts.partials.form')
@endsection
