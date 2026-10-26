@extends('layouts.employer-auth')

@section('title', 'Masuk Employer')

@section('content')
    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Masuk sebagai Employer</h1>
    <p class="mt-3 text-sm leading-relaxed text-slate-600">Gunakan akun perusahaan untuk melanjutkan pengelolaan lowongan.</p>
    <div class="mt-8"><x-auth-session-status :status="session('status')" /></div>
    <form method="POST" action="{{ route('employer.login.store') }}" class="mt-6 space-y-5">
        @csrf
        <div><label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label><input id="email" class="admin-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"><x-input-error :messages="$errors->get('email')" class="mt-2" /></div>
        <div><label for="password" class="mb-2 block text-sm font-medium text-slate-700">Kata sandi</label><input id="password" class="admin-input" type="password" name="password" required autocomplete="current-password"><x-input-error :messages="$errors->get('password')" class="mt-2" /></div>
        <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
            <label class="flex items-center gap-2 text-slate-600"><input name="remember" type="checkbox" class="rounded border-slate-300 text-[#B85B12] focus:ring-[#B85B12]">Ingat saya</label>
            <a class="admin-link" href="{{ route('password.request') }}">Lupa kata sandi?</a>
        </div>
        <button type="submit" class="admin-button-primary w-full">Masuk</button>
    </form>
    <p class="mt-8 text-center text-sm text-slate-600">Belum punya akun? <a href="{{ route('employer.register') }}" class="admin-link">Daftar di sini</a></p>
@endsection
