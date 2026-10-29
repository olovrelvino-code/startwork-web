<x-guest-layout>
    <h2 class="text-2xl font-bold tracking-tight">Lupa kata sandi</h2>
    <p class="mt-2 text-sm text-slate-600">Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>
    <x-auth-session-status class="mt-4" :status="session('status')" />
    <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
        @csrf
        <div><label for="email" class="block text-sm font-semibold">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="mt-2 block w-full rounded-lg border-slate-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600">@error('email')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
        <button type="submit" class="w-full rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Kirim tautan reset</button>
    </form>
    <a href="{{ route('login') }}" class="mt-6 inline-block text-sm font-semibold text-blue-700 hover:underline">Kembali ke login</a>
</x-guest-layout>
