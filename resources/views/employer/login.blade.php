@extends('layouts.employer-auth')

@section('title', 'Masuk Employer')

@section('content')
    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Masuk sebagai Employer</h1>
    <p class="mt-3 text-sm leading-relaxed text-slate-600">Gunakan akun perusahaan untuk melanjutkan pengelolaan lowongan.</p>
    <div class="mt-8"><x-auth-session-status :status="session('status')" /></div>
    <form method="POST" action="{{ route('employer.login.store') }}" class="auth-form mt-6 space-y-5" data-auth-login-form>
        @csrf
        <div><label for="email" class="auth-label">Email</label><input id="email" class="admin-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" @if ($errors->has('email')) aria-invalid="true" aria-describedby="employer-email-error" @endif><x-input-error :messages="$errors->get('email')" id="employer-email-error" class="auth-error mt-2" /></div>
        <div><label for="password" class="auth-label">Kata sandi</label><input id="password" class="admin-input" type="password" name="password" required autocomplete="current-password" @if ($errors->has('password')) aria-invalid="true" aria-describedby="employer-password-error" @endif><x-input-error :messages="$errors->get('password')" id="employer-password-error" class="auth-error mt-2" /></div>
        <div class="flex min-h-11 flex-wrap items-center justify-between gap-3 text-sm">
            <label class="inline-flex min-h-11 items-center gap-2 text-slate-700"><input name="remember" type="checkbox" class="auth-checkbox">Ingat saya</label>
            <a class="admin-link" href="{{ route('password.request') }}">Lupa kata sandi?</a>
        </div>
        <button type="submit" class="admin-button-primary w-full" data-loading-label="Memproses…">Masuk</button>
    </form>
    <p class="mt-8 text-center text-sm text-slate-600">Belum punya akun? <a href="{{ route('employer.register') }}" class="admin-link">Daftar di sini</a></p>
@endsection
