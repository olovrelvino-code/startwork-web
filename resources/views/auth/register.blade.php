<x-guest-layout>
    @php($pendingGoogle = $pendingGoogle ?? null)
    <x-slot:authSwitch>
        <div role="group" aria-label="Pilih jenis akun" data-role-switch data-role="jobseeker" class="auth-role-switch mb-4 rounded-xl p-1">
            <span class="auth-role-indicator" aria-hidden="true"></span>
            <button type="button" data-auth-role="jobseeker" aria-pressed="true" class="auth-role-button text-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Jobseeker</button>
            <button type="button" data-auth-role="employer" aria-pressed="false" class="auth-role-button text-slate-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Employer</button>
        </div>
    </x-slot:authSwitch>
    <section data-jobseeker-auth class="auth-card">
    <h2 class="text-2xl font-bold tracking-tight">Daftar Jobseeker</h2><p class="mt-2 text-sm text-slate-600">{{ $pendingGoogle ? 'Email Google sudah terverifikasi. Lengkapi data yang belum tersedia.' : 'Buat akun untuk menyimpan profil dan melamar lowongan internal.' }}</p>
    <x-auth-session-status class="mt-4" :status="session('status')" />
    <form method="POST" action="{{ route('register') }}" class="auth-register-form mt-7 space-y-4">
        @csrf
        @if ($pendingGoogle)<input type="hidden" name="registration_method" value="google">@endif
        <div><label for="name" class="block text-sm font-semibold">Nama lengkap</label><input id="name" name="name" value="{{ old('name', $pendingGoogle['name'] ?? '') }}" required autofocus autocomplete="name" maxlength="255" class="auth-register-input mt-2 block w-full rounded-lg">@error('name')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
        <div><label for="email" class="block text-sm font-semibold">Email</label><span class="auth-field-with-icon relative mt-2 block"><svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg><input id="email" name="email" type="email" value="{{ old('email', $pendingGoogle['email'] ?? '') }}" required autocomplete="email" maxlength="255" placeholder="Masukkan alamat email" class="auth-register-input block w-full rounded-lg" @if ($pendingGoogle) readonly @endif @error('email') aria-invalid="true" @enderror></span>@error('email')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
        <div><label for="phone" class="block text-sm font-semibold">Nomor telepon (opsional)</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="255" class="auth-register-input mt-2 block w-full rounded-lg">@error('phone')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
        @unless ($pendingGoogle)
            <div><label for="password" class="block text-sm font-semibold">Kata sandi</label><span class="auth-field-with-icon relative mt-2 block"><svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg><input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Masukkan password" class="auth-register-input block w-full rounded-lg" @error('password') aria-invalid="true" @enderror></span>@error('password')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
            <div><label for="password_confirmation" class="block text-sm font-semibold">Konfirmasi kata sandi</label><span class="auth-field-with-icon relative mt-2 block"><svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/></svg><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Ulangi password" class="auth-register-input block w-full rounded-lg"></span></div>
        @endunless
        <button type="submit" class="w-full rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">{{ $pendingGoogle ? 'Buat akun dengan Google' : 'Buat akun' }}</button>
    </form>
    @unless ($pendingGoogle)<p class="mt-4 text-center text-sm text-slate-600">Atau <a href="{{ route('google.redirect', 'jobseeker') }}" class="font-semibold text-blue-700 hover:underline">Daftar dengan Google</a></p>@endunless
    <p class="mt-6 text-center text-sm text-slate-600">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-blue-700 hover:underline">Masuk</a></p>
    </section>
    <div data-employer-auth data-login-url="{{ route('employer.login') }}" data-register-url="{{ route('employer.login', ['mode' => 'register']) }}" data-initial-url="{{ route('employer.login', ['mode' => 'register']) }}" data-setup-url="{{ route('employer.setup') }}" class="admin-shell hidden bg-white" hidden>
        <p data-employer-inline-status class="hidden" role="status" aria-live="polite"></p>
        <div data-employer-inline-content></div>
    </div>
    @vite('resources/js/employer.js')
</x-guest-layout>
