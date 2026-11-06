@extends('layouts.admin')

@section('title', 'Ubah Artikel')
@section('section_label', 'Artikel / Ubah')

@section('content')
    <x-admin.page-header :title="'Ubah '.$article->title" :back="route('admin.articles.index')" description="Perbarui isi, gambar sampul, atau status publikasi artikel." />
    @include('admin.articles.partials.form', ['article' => $article])
@endsection
