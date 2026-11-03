@extends('layouts.employer-auth')

@section('title', 'Masuk Employer')

@section('content')
    <section class="auth-card">
    <h1 class="text-2xl font-bold tracking-tight">Masuk sebagai Employer</h1>
    <p class="mt-2 text-sm text-slate-600">Gunakan akun perusahaan untuk melanjutkan pengelolaan lowongan.</p>
    <x-auth-session-status class="mt-4" :status="session('status')" />
    <form method="POST" action="{{ route('employer.login.store') }}" class="auth-form mt-6 space-y-4" data-auth-login-form>
        @csrf
        <div><label for="email" class="block text-sm font-semibold">Email</label><span class="relative mt-2 block"><input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" aria-describedby="employer-email-error"><svg data-auth-warning="email" class="auth-field-warning hidden" aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M12 3.5 22 21H2L12 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 9v5m0 3h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><p id="employer-email-error" data-auth-error="email" class="auth-field-error hidden" hidden></p></div>
        <div><label for="password" class="block text-sm font-semibold">Kata sandi</label><span class="relative mt-2 block"><input id="password" class="auth-input" type="password" name="password" required autocomplete="current-password" aria-describedby="employer-password-error"><svg data-auth-warning="password" class="auth-field-warning hidden" aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M12 3.5 22 21H2L12 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 9v5m0 3h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><p id="employer-password-error" data-auth-error="password" class="auth-field-error hidden" hidden></p></div>
        <p data-auth-feedback class="auth-feedback hidden" role="alert" aria-live="assertive" hidden></p>
        <div class="flex min-h-11 flex-wrap items-center justify-between gap-3 text-sm">
            <label class="inline-flex min-h-11 items-center gap-2 text-slate-600"><input name="remember" type="checkbox" class="auth-checkbox">Ingat saya</label>
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#183047] hover:underline">Lupa kata sandi?</a>
        </div>
        <button type="submit" class="admin-button-primary w-full" data-loading-label="Memproses…">Masuk</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">Belum punya akun? <a href="{{ route('employer.register') }}" class="font-semibold text-[#183047] hover:underline">Daftar di sini</a></p>
    </section>
@endsection
