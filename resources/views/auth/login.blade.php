<x-guest-layout>
    <h2 class="text-2xl font-bold tracking-tight">Masuk Jobseeker</h2><p class="mt-2 text-sm text-slate-600">Lanjutkan pencarian kerja dan pantau lamaran Anda.</p>
    <x-auth-session-status class="mt-4" :status="session('status')" />
    <form method="POST" action="{{ route('login.store') }}" data-auth-login-form class="mt-7 space-y-4">
        @csrf
        <div><label for="email" class="block text-sm font-semibold">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-2 block w-full rounded-lg border-slate-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600">@error('email')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
        <div><label for="password" class="block text-sm font-semibold">Kata sandi</label><input id="password" name="password" type="password" required autocomplete="current-password" class="mt-2 block w-full rounded-lg border-slate-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600">@error('password')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
        <div class="flex items-center justify-between gap-3"><label class="inline-flex items-center gap-2 text-sm text-slate-600"><input name="remember" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-600">Ingat saya</label><a href="{{ route('password.request') }}" class="text-sm font-semibold text-blue-700 hover:underline">Lupa kata sandi?</a></div>
        <button type="submit" data-loading-label="Memproses…" class="w-full rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-60">Masuk</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-blue-700 hover:underline">Daftar</a></p>
    <p class="mt-3 text-center text-sm text-slate-600">Mewakili perusahaan? <a href="{{ route('employer.login') }}" class="font-semibold text-slate-900 hover:underline">Masuk sebagai Employer</a></p>
</x-guest-layout>
