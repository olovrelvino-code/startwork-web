@extends('layouts.admin')

@section('title', 'Tulis Artikel')
@section('section_label', 'Artikel / Baru')

@section('content')
    <x-admin.page-header title="Tulis artikel" :back="route('admin.articles.index')" description="Siapkan informasi karier untuk blog publik. Draf tetap hanya terlihat di panel admin." />
    @include('admin.articles.partials.form')
@endsection
