<x-guest-layout>
    <x-slot:authSwitch>
        <div role="group" aria-label="Pilih jenis akun" data-role-switch data-role="jobseeker" class="auth-role-switch mb-4 rounded-xl p-1">
            <span class="auth-role-indicator" aria-hidden="true"></span>
            <button type="button" data-auth-role="jobseeker" aria-pressed="true" class="auth-role-button text-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Jobseeker</button>
            <button type="button" data-auth-role="employer" aria-pressed="false" class="auth-role-button text-slate-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Employer</button>
        </div>
    </x-slot:authSwitch>
    <section data-jobseeker-auth class="auth-card">
    <h2 class="text-2xl font-bold tracking-tight">Daftar Jobseeker</h2><p class="mt-2 text-sm text-slate-600">Buat akun untuk menyimpan profil dan melamar lowongan internal.</p>
    <form method="POST" action="{{ route('register') }}" class="auth-register-form mt-7 space-y-4">
        @csrf
        <div><label for="name" class="block text-sm font-semibold">Nama lengkap</label><input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" maxlength="255" class="auth-register-input mt-2 block w-full rounded-lg">@error('name')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
        <div><label for="email" class="block text-sm font-semibold">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" maxlength="255" class="auth-register-input mt-2 block w-full rounded-lg">@error('email')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
        <div><label for="phone" class="block text-sm font-semibold">Nomor telepon (opsional)</label><input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="255" class="auth-register-input mt-2 block w-full rounded-lg">@error('phone')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
        <div><label for="password" class="block text-sm font-semibold">Kata sandi</label><input id="password" name="password" type="password" required autocomplete="new-password" class="auth-register-input mt-2 block w-full rounded-lg">@error('password')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
        <div><label for="password_confirmation" class="block text-sm font-semibold">Konfirmasi kata sandi</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="auth-register-input mt-2 block w-full rounded-lg"></div>
        <button type="submit" class="w-full rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Buat akun</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-blue-700 hover:underline">Masuk</a></p>
    </section>
    <div data-employer-auth data-login-url="{{ route('employer.login') }}" data-register-url="{{ route('employer.register') }}" data-setup-url="{{ route('employer.setup') }}" class="admin-shell hidden bg-white" hidden>
        <p data-employer-inline-status class="hidden" role="status" aria-live="polite"></p>
        <div data-employer-inline-content></div>
    </div>
    @vite('resources/js/employer.js')
</x-guest-layout>
