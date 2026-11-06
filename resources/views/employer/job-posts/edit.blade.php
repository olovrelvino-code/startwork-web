@extends('layouts.employer')

@section('title', 'Ubah lowongan')
@section('section_label', 'Lowongan kerja')

@section('content')
    <x-admin.page-header :title="'Ubah '.$job->title" :back="route('employer.job-posts.show', $job->id)" description="Perubahan isi lowongan tidak mengubah statusnya." />
    @include('employer.job-posts.partials.form')
@endsection
