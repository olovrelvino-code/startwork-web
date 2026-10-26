@php($registrationStep = 1)
@extends('layouts.employer-auth')

@section('title', 'Daftar Employer')
@section('content_width', 'max-w-[560px]')

@section('content')
    <div class="border-b border-slate-200 pb-6">
        <p class="text-sm font-medium text-[#9A4A0A]">Langkah 1 dari 2</p>
        <x-employer.registration-progress :step="$registrationStep" class="mt-4" />
        <h1 class="mt-7 text-3xl font-semibold tracking-tight text-slate-900">Buat akun</h1>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">Isi data akun Anda. Profil perusahaan dilengkapi pada langkah berikutnya.</p>
    </div>

    <div id="employer-feedback" class="mt-6 hidden" aria-live="polite"></div>
    <form data-employer-form data-success-url="{{ route('employer.setup') }}" action="{{ url('/api/employer/register') }}" method="POST" class="mt-6 space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="mb-2 block text-sm font-medium text-slate-700" for="full_name">Nama Lengkap</label><input class="admin-input min-h-11" id="full_name" name="full_name" required maxlength="255" autocomplete="name"></div>
            <div><label class="mb-2 block text-sm font-medium text-slate-700" for="phone">Nomor telepon</label><input class="admin-input min-h-11" id="phone" name="phone" type="tel" required maxlength="255" autocomplete="tel"></div>
        </div>
        <div><label class="mb-2 block text-sm font-medium text-slate-700" for="email">Email</label><input class="admin-input min-h-11" id="email" name="email" type="email" required maxlength="255" autocomplete="email"></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="mb-2 block text-sm font-medium text-slate-700" for="password">Kata sandi</label><input class="admin-input min-h-11" id="password" name="password" type="password" required minlength="8" autocomplete="new-password"></div>
            <div><label class="mb-2 block text-sm font-medium text-slate-700" for="password_confirmation">Konfirmasi kata sandi</label><input class="admin-input min-h-11" id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div>
        </div>
        <p class="text-sm text-slate-500">Gunakan kata sandi minimal 8 karakter.</p>
        <button class="admin-button-primary mt-2 w-full" type="submit">Buat akun dan lanjutkan</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">Sudah punya akun? <a href="{{ route('employer.login') }}" class="admin-link">Masuk sebagai Employer</a></p>
@endsection
