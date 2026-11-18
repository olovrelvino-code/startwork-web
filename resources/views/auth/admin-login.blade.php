<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Admin Internal · Start Work</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell admin-internal-shell min-h-screen bg-white antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-2">
        <aside class="auth-visual auth-visual--admin hidden flex-col justify-between px-10 py-10 lg:flex xl:px-16" aria-label="Portal internal Start Work">
            <a href="{{ url('/') }}" class="w-fit text-xl font-bold tracking-tight text-white" aria-label="Start Work, halaman utama">startwork<span class="text-[#E6A16D]">.</span></a>
            <p class="max-w-md text-3xl font-semibold leading-tight tracking-tight text-white">Ruang kerja operasional Start Work.</p>
        </aside>
        <main class="flex min-h-screen items-center px-5 py-10 sm:px-10 lg:px-16">
        <div class="mx-auto w-full max-w-[420px]">
            <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight text-slate-900 lg:hidden" aria-label="Start Work, halaman utama">startwork<span class="text-[#B85B12]">.</span></a>
            <h1 class="mt-10 text-3xl font-semibold tracking-tight text-slate-900 lg:mt-0">Admin Internal</h1>
            <p class="mt-3 text-sm leading-relaxed text-slate-600">Masuk untuk mengelola operasional Start Work.</p>
            <div class="mt-6"><x-auth-session-status :status="session('status')" /></div>
            <form method="POST" action="{{ route('admin.login.store') }}" class="auth-form mt-6 space-y-5" data-auth-login-form>
                @csrf
                <div>
                    <label for="email" class="auth-label">Email</label>
                    <span class="relative block">
                        <svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input id="email" class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" @if ($errors->has('email')) aria-invalid="true" aria-describedby="admin-email-error" @endif>
                    </span>
                    <x-input-error :messages="$errors->get('email')" id="admin-email-error" class="auth-error mt-2" />
                </div>
                <div x-data="{ passwordVisible: false }">
                    <label for="password" class="auth-label">Kata sandi</label>
                    <span class="relative block">
                        <svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg>
                        <input id="password" class="auth-input" x-bind:type="passwordVisible ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password" @if ($errors->has('password')) aria-invalid="true" aria-describedby="admin-password-error" @endif>
                        <button type="button" class="auth-toggle-password" x-on:click="passwordVisible = ! passwordVisible" x-bind:aria-label="passwordVisible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" x-bind:aria-pressed="passwordVisible.toString()" aria-label="Tampilkan kata sandi" aria-pressed="false"><svg x-show="! passwordVisible" aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg x-cloak x-show="passwordVisible" aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a14.7 14.7 0 0 1-3.1 3.9M6.2 6.2C3.5 8.1 2 12 2 12s3.6 7 10 7a10.8 10.8 0 0 0 3.3-.5"/></svg></button>
                    </span>
                    <x-input-error :messages="$errors->get('password')" id="admin-password-error" class="auth-error mt-2" />
                </div>
                <div class="flex min-h-11 flex-wrap items-center justify-between gap-3 text-sm">
                    <label class="inline-flex min-h-11 items-center gap-2 text-slate-700"><input name="remember" type="checkbox" class="auth-checkbox">Ingat saya</label>
                    <a class="admin-link" href="{{ route('password.request') }}">Lupa kata sandi?</a>
                </div>
                <button type="submit" class="admin-button-primary w-full" data-loading-label="Memproses…">Masuk</button>
            </form>
        </div>
        </main>
    </div>
</body>
</html>
