@extends('layouts.admin')

@section('title', 'Ubah Lowongan')
@section('section_label', 'Lowongan Kerja / Ubah')

@section('content')
    <x-admin.page-header :title="'Ubah '.$job->title" :back="route('admin.job-posts.show', $job->id)" description="Perubahan konten tidak mengubah perusahaan, pembuat, atau status lowongan." />
    @include('admin.job-posts.partials.form', ['job' => $job])
@endsection
