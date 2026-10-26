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
<body class="admin-shell min-h-screen antialiased">
    <main class="flex min-h-screen items-center justify-center px-5 py-10">
        <div class="w-full max-w-[420px] rounded-2xl border border-slate-200 bg-white p-6 sm:p-9">
            <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight text-slate-900" aria-label="Start Work, halaman utama">startwork<span class="text-[#B85B12]">.</span></a>
            <h1 class="mt-10 text-2xl font-semibold tracking-tight text-slate-900">Admin Internal</h1>
            <p class="mt-2 text-sm text-slate-600">Masuk untuk mengelola operasional Start Work.</p>
            <div class="mt-6"><x-auth-session-status :status="session('status')" /></div>
            <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-5">
                @csrf
                <div><label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label><input id="email" class="admin-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"><x-input-error :messages="$errors->get('email')" class="mt-2" /></div>
                <div><label for="password" class="mb-2 block text-sm font-medium text-slate-700">Kata sandi</label><input id="password" class="admin-input" type="password" name="password" required autocomplete="current-password"><x-input-error :messages="$errors->get('password')" class="mt-2" /></div>
                <div class="flex items-center justify-between gap-3 text-sm">
                    <label class="flex items-center gap-2 text-slate-600"><input name="remember" type="checkbox" class="rounded border-slate-300 text-[#B85B12] focus:ring-[#B85B12]">Ingat saya</label>
                    <a class="admin-link" href="{{ route('password.request') }}">Lupa kata sandi?</a>
                </div>
                <button type="submit" class="admin-button-primary w-full">Masuk</button>
            </form>
        </div>
    </main>
</body>
</html>
