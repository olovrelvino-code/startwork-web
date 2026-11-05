<x-guest-layout>
    <x-slot:authSwitch>
        <div role="group" aria-label="Pilih jenis akun" data-role-switch data-role="jobseeker" class="auth-role-switch mb-4 rounded-xl p-1">
            <span class="auth-role-indicator" aria-hidden="true"></span>
            <button type="button" data-auth-role="jobseeker" aria-pressed="true" class="auth-role-button text-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Jobseeker</button>
            <button type="button" data-auth-role="employer" aria-pressed="false" class="auth-role-button text-slate-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Employer</button>
        </div>
    </x-slot:authSwitch>
    <div data-jobseeker-auth class="auth-card">
        <h2 class="text-2xl font-bold tracking-tight">Masuk Jobseeker</h2>
        <p class="mt-2 text-sm text-slate-600">Lanjutkan pencarian kerja dan pantau lamaran Anda.</p>
        <x-auth-session-status class="mt-4" :status="session('status')" />
        @error('google')<p class="auth-feedback mt-4" role="alert">{{ $message }}</p>@enderror
        <form method="POST" action="{{ route('login.store') }}" data-auth-login-form class="auth-form auth-login-form mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-semibold">Email</label>
                <span class="relative mt-2 block">
                    <svg aria-hidden="true" class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Masukkan alamat email" class="auth-input" aria-describedby="email-error">
                </span>
                <div class="flex items-center gap-1.5"><svg data-auth-warning="email" class="auth-field-warning hidden" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3.5 22 21H2L12 3.5Z" stroke-linejoin="round"/><path d="M12 9v5m0 3h.01" stroke-linecap="round"/></svg><p id="email-error" data-auth-error="email" class="auth-field-error hidden" hidden></p></div>
            </div>
            <div>
                <label for="password" class="block text-sm font-semibold">Kata sandi</label>
                <span class="relative mt-2 block">
                    <svg aria-hidden="true" class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg>
                    <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan password" class="auth-input" aria-describedby="password-error">
                    <button type="button" data-password-toggle aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi" aria-pressed="false" class="auth-toggle-password"><svg data-password-show aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><svg data-password-hide aria-hidden="true" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a14.7 14.7 0 0 1-3.1 3.9M6.2 6.2C3.5 8.1 2 12 2 12s3.6 7 10 7a10.8 10.8 0 0 0 3.3-.5"/></svg></button>
                </span>
                <div class="flex items-center gap-1.5"><svg data-auth-warning="password" class="auth-field-warning hidden" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3.5 22 21H2L12 3.5Z" stroke-linejoin="round"/><path d="M12 9v5m0 3h.01" stroke-linecap="round"/></svg><p id="password-error" data-auth-error="password" class="auth-field-error hidden" hidden></p></div>
            </div>
            <p data-auth-feedback class="auth-feedback hidden" role="alert" aria-live="assertive" hidden></p>
            <div class="flex min-h-11 flex-wrap items-center justify-between gap-3 text-sm"><label class="inline-flex min-h-11 items-center gap-2 text-slate-600"><input name="remember" type="checkbox" class="rounded border-slate-300 accent-[#B85B12] focus:ring-[#B85B12]">Ingat saya</label><a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#183047] hover:text-[#95420D] hover:underline">Lupa kata sandi?</a></div>
            <button type="submit" data-loading-label="Memprosesâ€¦" class="auth-submit-button">Masuk</button>
        </form>
        <div class="my-5 flex items-center gap-3 text-xs text-slate-500" aria-hidden="true"><span class="h-px flex-1 bg-slate-200"></span>atau<span class="h-px flex-1 bg-slate-200"></span></div>
        <a href="{{ route('google.redirect', 'jobseeker') }}" data-google-login class="flex min-h-11 w-full items-center justify-center gap-3 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-[#183047] transition-colors hover:border-[#B85B12] hover:bg-orange-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]" aria-label="Masuk dengan Google sebagai Jobseeker">
            <img src="{{ asset('images/asset/google-g.png') }}" alt="" aria-hidden="true" width="20" height="20" class="h-5 w-5 shrink-0 object-contain">
            <span data-google-label>Masuk dengan Google</span>
        </a>
        <p class="mt-6 text-center text-sm text-slate-600">Belum punya akun? <a href="{{ route('login', ['mode' => 'register']) }}" class="font-semibold text-[#183047] hover:text-[#95420D] hover:underline">Daftar</a></p>
    </div>
    <div data-employer-auth data-login-url="{{ route('employer.login') }}" data-register-url="{{ route('employer.login', ['mode' => 'register']) }}" data-setup-url="{{ route('employer.setup') }}" class="admin-shell hidden bg-white" hidden>
        <p data-employer-inline-status class="hidden" role="status" aria-live="polite"></p>
        <div data-employer-inline-content></div>
    </div>
    @vite('resources/js/employer.js')
</x-guest-layout>
