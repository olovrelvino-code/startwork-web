@php($registrationStep = 1)
@php($pendingGoogle = $pendingGoogle ?? null)
@php($completingAccount = $completingAccount ?? false)
@php($account = $account ?? null)
@extends('layouts.employer-auth')

@section('title', 'Daftar Employer')
@section('content_width', 'max-w-[560px]')

@section('content')
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <div class="border-b border-slate-200 pb-6">
        <p class="text-sm font-medium text-[#9A4A0A]">Langkah 1 dari 2</p>
        <x-employer.registration-progress :step="$registrationStep" class="mt-4" />
        <h2 class="mt-7 text-3xl font-semibold tracking-tight text-slate-900">{{ $completingAccount ? 'Lengkapi akun' : 'Buat akun' }}</h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $completingAccount ? 'Lengkapi data akun yang belum tersimpan sebelum melanjutkan ke profil perusahaan.' : ($pendingGoogle ? 'Email Google sudah terverifikasi. Lengkapi data akun yang belum tersedia, lalu lanjutkan ke profil perusahaan.' : 'Isi data akun Anda. Profil perusahaan dilengkapi pada langkah berikutnya.') }}</p>
    </div>

    <div id="employer-feedback" class="mt-6 hidden" aria-live="polite"></div>
    <form data-employer-form data-method="{{ $completingAccount ? 'PATCH' : 'POST' }}" data-success-url="{{ route('employer.setup') }}" action="{{ $completingAccount ? route('employer.setup.account') : url('/api/employer/register') }}" method="POST" class="auth-register-form mt-6 space-y-4">
        @csrf
        @if ($pendingGoogle)<input type="hidden" name="registration_method" value="google">@endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="mb-2 block text-sm font-medium text-slate-700" for="full_name">Nama Lengkap</label><input class="admin-input auth-register-input min-h-11" id="full_name" name="full_name" value="{{ old('full_name', $account->full_name ?? $pendingGoogle['name'] ?? '') }}" required maxlength="255" autocomplete="name"></div>
            <div><label class="mb-2 block text-sm font-medium text-slate-700" for="phone">Nomor telepon</label><input class="admin-input auth-register-input min-h-11" id="phone" name="phone" type="tel" value="{{ old('phone', $account->phone ?? '') }}" required maxlength="255" autocomplete="tel"></div>
        </div>
        <div><label class="mb-2 block text-sm font-medium text-slate-700" for="email">Email</label><span class="auth-field-with-icon relative block"><svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg><input class="admin-input auth-register-input min-h-11" id="email" name="email" type="email" value="{{ old('email', $account->email ?? $pendingGoogle['email'] ?? '') }}" required maxlength="255" autocomplete="email" placeholder="Masukkan alamat email" @if ($pendingGoogle || $completingAccount) readonly @endif></span></div>
        @unless ($pendingGoogle || $completingAccount)
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="mb-2 block text-sm font-medium text-slate-700" for="password">Kata sandi</label><span class="auth-field-with-icon relative block"><svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg><input class="admin-input auth-register-input min-h-11" id="password" name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="Masukkan password"></span></div>
            <div><label class="mb-2 block text-sm font-medium text-slate-700" for="password_confirmation">Konfirmasi kata sandi</label><span class="auth-field-with-icon relative block"><svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg><input class="admin-input auth-register-input min-h-11" id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" placeholder="Ulangi password"></span></div>
        </div>
        <p class="text-sm text-slate-500">Gunakan kata sandi minimal 8 karakter.</p>
        @endunless
        <button class="admin-button-primary mt-2 w-full" type="submit">{{ $completingAccount ? 'Simpan data akun dan lanjutkan' : ($pendingGoogle ? 'Buat akun dengan Google dan lanjutkan' : 'Buat akun dan lanjutkan') }}</button>
    </form>
    @unless ($completingAccount)<p class="mt-6 text-center text-sm text-slate-600">Sudah punya akun? <a href="{{ route('employer.login') }}" class="admin-link">Masuk sebagai Employer</a></p>@endunless
@endsection
