@extends($layout)

@section('title', 'Settings')
@section('section_label', 'Settings')

@section('content')
    @php
        $initialTab = $errors->updatePassword->any() || session('status') === 'password-updated' ? 'password' : 'profile';
    @endphp
    <div class="mx-auto max-w-5xl" x-data="{ tab: '{{ $initialTab }}' }">
        <x-admin.page-header title="Settings" :back="route($dashboardRoute)" description="Kelola identitas dan keamanan akun Anda." />

        <div class="grid gap-6 md:grid-cols-[13rem_minmax(0,1fr)]">
            <nav class="admin-panel flex h-fit gap-1 p-2 md:flex-col" aria-label="Pengaturan akun">
                <button type="button" @click="tab = 'profile'" :aria-pressed="(tab === 'profile').toString()" :class="tab === 'profile' ? 'bg-[#EAF0F2] text-[#17394B] border-[#DE7C23]' : 'border-transparent text-slate-600 hover:bg-slate-50'" class="flex-1 rounded-lg border-l-2 px-4 py-3 text-left text-sm font-semibold transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#B85B12] md:flex-none">Profile</button>
                <button type="button" @click="tab = 'password'" :aria-pressed="(tab === 'password').toString()" :class="tab === 'password' ? 'bg-[#EAF0F2] text-[#17394B] border-[#DE7C23]' : 'border-transparent text-slate-600 hover:bg-slate-50'" class="flex-1 rounded-lg border-l-2 px-4 py-3 text-left text-sm font-semibold transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#B85B12] md:flex-none">Password</button>
            </nav>

            <section x-cloak x-show="tab === 'profile'" class="admin-panel min-w-0 p-5 sm:p-7" aria-labelledby="settings-profile-title">
                <h2 id="settings-profile-title" class="text-xl font-semibold text-[#17394B]">Profile</h2>
                <p class="mt-1 text-sm text-slate-600">Nama dan email ini tampil pada akun {{ $user->role === 'admin' ? 'Admin Internal' : 'Employer' }} Anda.</p>
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-7 max-w-xl space-y-5">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_account_form" value="profile">
                    <div><label class="admin-label" for="account-name">Nama</label><input class="admin-input" id="account-name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">@error('name')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div><label class="admin-label" for="account-email">Email</label><input class="admin-input" id="account-email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">@error('email')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    <div><label class="admin-label" for="account-photo">Foto profil</label><input class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-[#EAF0F2] file:px-3 file:py-2 file:font-semibold file:text-[#17394B]" id="account-photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp"><p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WebP hingga 2 MB.</p>@error('profile_photo')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror</div>
                    @if (session('status') === 'profile-updated')<p class="text-sm font-medium text-emerald-700" role="status">Profil berhasil diperbarui.</p>@endif
                    <button type="submit" class="admin-button-primary">Simpan perubahan</button>
                </form>
            </section>

            <section x-cloak x-show="tab === 'password'" class="admin-panel min-w-0 p-5 sm:p-7" aria-labelledby="settings-password-title">
                <h2 id="settings-password-title" class="text-xl font-semibold text-[#17394B]">Password</h2>
                <p class="mt-1 text-sm text-slate-600">Gunakan password baru yang kuat untuk menjaga keamanan akun.</p>
                <form method="POST" action="{{ route('password.update') }}" class="mt-7 max-w-xl space-y-5">
                    @csrf
                    @method('PUT')
                    <div><label class="admin-label" for="account-current-password">Password saat ini</label><input class="admin-input" id="account-current-password" name="current_password" type="password" required autocomplete="current-password">@foreach ($errors->updatePassword->get('current_password') as $message)<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@endforeach</div>
                    <div><label class="admin-label" for="account-new-password">Password baru</label><input class="admin-input" id="account-new-password" name="password" type="password" required autocomplete="new-password">@foreach ($errors->updatePassword->get('password') as $message)<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@endforeach</div>
                    <div><label class="admin-label" for="account-confirm-password">Konfirmasi password baru</label><input class="admin-input" id="account-confirm-password" name="password_confirmation" type="password" required autocomplete="new-password">@foreach ($errors->updatePassword->get('password_confirmation') as $message)<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@endforeach</div>
                    @if (session('status') === 'password-updated')<p class="text-sm font-medium text-emerald-700" role="status">Password berhasil diperbarui.</p>@endif
                    <button type="submit" class="admin-button-primary">Simpan password</button>
                </form>
            </section>
        </div>
    </div>
@endsection
