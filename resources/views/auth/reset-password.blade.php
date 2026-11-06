<x-guest-layout>
    <h2 class="text-2xl font-bold tracking-tight">Atur ulang kata sandi</h2>
    <form method="POST" action="{{ route('password.store') }}" class="mt-7 space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div><label for="email" class="block text-sm font-semibold">Email</label><input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="email" class="mt-2 block w-full rounded-lg border-slate-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600">@error('email')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>

        <div><label for="password" class="block text-sm font-semibold">Kata sandi baru</label><input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 block w-full rounded-lg border-slate-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600">@error('password')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>

        <div><label for="password_confirmation" class="block text-sm font-semibold">Konfirmasi kata sandi</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 block w-full rounded-lg border-slate-300 focus:border-blue-600 focus:ring-1 focus:ring-blue-600">@error('password_confirmation')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror</div>
        <button type="submit" class="w-full rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Simpan kata sandi baru</button>
    </form>
</x-guest-layout>
