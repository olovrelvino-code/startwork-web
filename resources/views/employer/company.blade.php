@extends('layouts.employer')

@php($isSetup = $setup ?? false)

@section('title', $isSetup ? 'Setup perusahaan' : 'Perusahaan')
@section('section_label', $isSetup ? 'Setup perusahaan' : 'Perusahaan')

@section('content')
    <x-admin.page-header :title="$isSetup ? 'Setup perusahaan' : 'Profil perusahaan'" :description="$isSetup ? 'Lengkapi profil perusahaan sebelum masuk ke ruang kerja Employer.' : 'Kelola informasi perusahaan yang tampil bersama lowongan Anda.'" />

    @if ($isSetup)
        <ol class="mb-8 flex flex-wrap gap-x-8 gap-y-3 text-sm" aria-label="Progres pendaftaran Employer">
            <li class="font-medium text-slate-500">Akun Employer dibuat</li>
            <li class="border-b-2 border-[#B85B12] pb-2 font-semibold text-slate-900" aria-current="step">Profil perusahaan</li>
        </ol>
    @endif

    <section class="admin-panel p-5 sm:p-6" aria-labelledby="company-details-title">
        <h2 id="company-details-title" class="mb-5 text-lg font-semibold text-slate-900">Informasi perusahaan</h2>
        <form data-employer-form data-method="{{ $company ? 'PUT' : 'POST' }}" data-success-url="{{ $isSetup ? route('employer.setup') : route('employer.company') }}" data-success-message="Profil perusahaan berhasil disimpan." action="{{ url('/api/employer/company') }}" class="grid gap-5 sm:grid-cols-2">
            @csrf
            <div><label class="admin-label" for="company-name">Nama perusahaan</label><input class="admin-input" id="company-name" name="name" required maxlength="255" value="{{ $company->name ?? '' }}"></div>
            <div><label class="admin-label" for="company-location">Lokasi</label><input class="admin-input" id="company-location" name="location" required maxlength="255" value="{{ $company->location ?? '' }}"></div>
            <div><label class="admin-label" for="company-website">Website resmi</label><input class="admin-input" id="company-website" name="website" type="url" maxlength="255" value="{{ $company->website ?? '' }}"></div>
            <div><label class="admin-label" for="company-contact">Tautan kontak</label><input class="admin-input" id="company-contact" name="contact_url" type="url" maxlength="255" value="{{ $company->contact_url ?? '' }}"></div>
            <div class="sm:col-span-2"><label class="admin-label" for="company-description">Deskripsi</label><textarea class="admin-input min-h-32" id="company-description" name="description">{{ $company->description ?? '' }}</textarea></div>
            <div class="sm:col-span-2"><button type="submit" class="admin-button-primary">{{ $company ? 'Simpan perubahan' : ($isSetup ? 'Simpan & lanjutkan' : 'Buat perusahaan') }}</button></div>
        </form>
    </section>

    @if ($company)
        <section class="admin-panel mt-7 p-5 sm:p-6" aria-labelledby="company-logo-title">
            <h2 id="company-logo-title" class="mb-4 text-lg font-semibold text-slate-900">Logo perusahaan</h2>
            @if ($company->logo)
                <img src="{{ url('/api/companies/'.$company->id.'/logo') }}" alt="Logo {{ $company->name }}" class="mb-5 h-24 w-24 rounded-lg border border-slate-200 object-contain">
            @endif
            <form data-employer-form data-success-url="{{ $isSetup ? route('employer.setup') : route('employer.company') }}" data-success-message="Logo perusahaan berhasil diperbarui." action="{{ url('/api/employer/company/logo') }}" method="POST" enctype="multipart/form-data" class="max-w-md space-y-5">
                @csrf
                <x-admin.file-field name="logo" label="Pilih logo" required />
                <button type="submit" class="admin-button-secondary">Unggah logo</button>
            </form>
        </section>
        @if ($isSetup)
            <div class="mt-7 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-slate-600">Logo bersifat opsional dan dapat ditambahkan nanti.</p>
                <a href="{{ route('employer.dashboard') }}" class="admin-button-primary">Lanjut ke dashboard</a>
            </div>
        @endif
    @endif
@endsection
