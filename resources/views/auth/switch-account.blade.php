<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="session-user-id" content="{{ $user->id }}" data-role="{{ $user->role }}">
    <title>Ganti akun · Start Work</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-shell min-h-screen antialiased">
    @php
        $roleLabels = ['admin' => 'Admin', 'employer' => 'Employer', 'jobseeker' => 'Jobseeker'];
    @endphp
    <main class="flex min-h-screen items-center justify-center px-5 py-10">
        <div class="w-full max-w-[460px] rounded-2xl border border-slate-200 bg-white p-6 sm:p-9">
            <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight text-slate-900">startwork<span class="text-[#B85B12]">.</span></a>
            <p class="mt-10 text-sm font-medium text-[#9A4A0A]">Pergantian akun</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Masuk sebagai {{ $roleLabels[$role] }}</h1>
            <p class="mt-4 text-sm leading-relaxed text-slate-600">Sesi browser ini sedang digunakan oleh {{ $user->full_name ?? $user->email }} ({{ $user->email }}), sebagai {{ $roleLabels[$user->role] ?? $user->role }}. Semua tab memakai sesi yang sama.</p>
            <p class="mt-3 text-sm leading-relaxed text-slate-600">Keluar dari akun ini untuk melanjutkan ke login {{ $roleLabels[$role] }}.</p>
            <nav aria-label="Tujuan login" class="mt-6 flex flex-wrap gap-2">
                @foreach ($roleLabels as $targetRole => $label)
                    @if ($targetRole !== $user->role)
                        <a href="{{ route('account.switch', ['role' => $targetRole]) }}" @if ($targetRole === $role) aria-current="page" @endif @class([
                            'rounded-lg border px-3 py-2 text-sm font-medium focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#B85B12]',
                            'border-[#B85B12] bg-orange-50 text-slate-900' => $targetRole === $role,
                            'border-slate-200 text-slate-600 hover:border-slate-400' => $targetRole !== $role,
                        ])>{{ $label }}</a>
                    @endif
                @endforeach
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="mt-8">
                @csrf
                <input type="hidden" name="next" value="{{ $role }}">
                <button type="submit" class="admin-button-primary w-full">Keluar dan lanjut ke login</button>
            </form>
            <a href="{{ route('dashboard') }}" class="admin-link mt-5 inline-block text-sm">Tetap gunakan akun ini</a>
        </div>
    </main>
</body>
</html>
