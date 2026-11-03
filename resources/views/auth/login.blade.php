<x-guest-layout>
    <x-slot:authSwitch>
        <div role="group" aria-label="Pilih jenis akun" data-role-switch data-role="jobseeker" class="auth-role-switch mb-4 rounded-xl p-1">
            <span class="auth-role-indicator" aria-hidden="true"></span>
            <button type="button" data-auth-role="jobseeker" aria-pressed="true" class="auth-role-button text-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Jobseeker</button>
            <button type="button" data-auth-role="employer" aria-pressed="false" class="auth-role-button text-slate-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Employer</button>
        </div>
    </x-slot:authSwitch>
    <div data-jobseeker-auth class="auth-card">
    <h2 class="text-2xl font-bold tracking-tight">Masuk Jobseeker</h2><p class="mt-2 text-sm text-slate-600">Lanjutkan pencarian kerja dan pantau lamaran Anda.</p>
    <x-auth-session-status class="mt-4" :status="session('status')" />
    <form method="POST" action="{{ route('login.store') }}" data-auth-login-form class="auth-form mt-6 space-y-4">
        @csrf
        <div><label for="email" class="block text-sm font-semibold">Email</label><span class="relative mt-2 block"><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="auth-input" aria-describedby="email-error"><svg data-auth-warning="email" class="auth-field-warning hidden" aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M12 3.5 22 21H2L12 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 9v5m0 3h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><p id="email-error" data-auth-error="email" class="auth-field-error hidden" hidden></p></div>
        <div><label for="password" class="block text-sm font-semibold">Kata sandi</label><span class="relative mt-2 block"><input id="password" name="password" type="password" required autocomplete="current-password" class="auth-input" aria-describedby="password-error"><svg data-auth-warning="password" class="auth-field-warning hidden" aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M12 3.5 22 21H2L12 3.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 9v5m0 3h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span><p id="password-error" data-auth-error="password" class="auth-field-error hidden" hidden></p></div>
        <p data-auth-feedback class="auth-feedback hidden" role="alert" aria-live="assertive" hidden></p>
        <div class="flex min-h-11 flex-wrap items-center justify-between gap-3 text-sm"><label class="inline-flex min-h-11 items-center gap-2 text-slate-600"><input name="remember" type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-600">Ingat saya</label><a href="{{ route('password.request') }}" class="text-sm font-semibold text-blue-700 hover:underline">Lupa kata sandi?</a></div>
        <button type="submit" data-loading-label="Memproses…" class="w-full rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-60">Masuk</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-blue-700 hover:underline">Daftar</a></p>
    </div>
    <div data-employer-auth data-login-url="{{ route('employer.login') }}" data-register-url="{{ route('employer.register') }}" data-setup-url="{{ route('employer.setup') }}" class="admin-shell hidden bg-white" hidden>
        <p data-employer-inline-status class="hidden" role="status" aria-live="polite"></p>
        <div data-employer-inline-content></div>
    </div>
    @vite('resources/js/employer.js')
</x-guest-layout>
