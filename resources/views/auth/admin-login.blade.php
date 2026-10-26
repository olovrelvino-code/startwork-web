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
<body class="admin-shell min-h-screen bg-white antialiased">
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
                <div><label for="email" class="auth-label">Email</label><input id="email" class="admin-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" @if ($errors->has('email')) aria-invalid="true" aria-describedby="admin-email-error" @endif><x-input-error :messages="$errors->get('email')" id="admin-email-error" class="auth-error mt-2" /></div>
                <div><label for="password" class="auth-label">Kata sandi</label><input id="password" class="admin-input" type="password" name="password" required autocomplete="current-password" @if ($errors->has('password')) aria-invalid="true" aria-describedby="admin-password-error" @endif><x-input-error :messages="$errors->get('password')" id="admin-password-error" class="auth-error mt-2" /></div>
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
