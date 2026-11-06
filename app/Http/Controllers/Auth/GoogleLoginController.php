<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    public function redirect(Request $request, string $role): RedirectResponse
    {
        $loginRoute = $role === 'employer' ? 'employer.login' : 'login';

        if (! config('services.google.client_id') || ! config('services.google.client_secret') || ! config('services.google.redirect')) {
            return redirect()->route($loginRoute)->withErrors(['google' => 'Login Google belum dikonfigurasi. Gunakan email dan kata sandi untuk masuk.']);
        }

        $callback = parse_url((string) config('services.google.redirect'));
        if (! is_array($callback) || ! in_array($callback['scheme'] ?? null, ['http', 'https'], true) || empty($callback['host'])) {
            return redirect()->route($loginRoute)->withErrors(['google' => 'Alamat callback Google tidak valid. Hubungi pengelola situs.']);
        }

        $callbackOrigin = $callback['scheme'].'://'.$callback['host'].(isset($callback['port']) ? ':'.$callback['port'] : '');
        if (strcasecmp($request->getSchemeAndHttpHost(), $callbackOrigin) !== 0) {
            return redirect()->to($callbackOrigin.route('google.redirect', $role, absolute: false));
        }

        $request->session()->forget('google_oauth_pending');
        $request->session()->forget('google_oauth_registration');
        $request->session()->put('google_oauth_role', $role);
        $request->session()->put('google_oauth_started_at', now()->timestamp);

        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $role = $request->session()->pull('google_oauth_role');
        $startedAt = $request->session()->pull('google_oauth_started_at');
        $loginRoute = $role === 'employer' ? 'employer.login' : 'login';

        if ($role === 'employer') {
            return redirect()->route('employer.login');
        }

        if (! in_array($role, ['jobseeker', 'employer'], true) || ! is_int($startedAt) || now()->timestamp - $startedAt > 600) {
            return redirect()->route($loginRoute)->withErrors(['google' => 'Sesi login Google berakhir. Silakan coba lagi.']);
        }

        if ($request->has('error')) {
            return redirect()->route($loginRoute)->withErrors(['google' => 'Login Google dibatalkan atau tidak diizinkan. Silakan coba lagi.']);
        }

        if (! $request->filled('code') || ! $request->filled('state')) {
            return redirect()->route($loginRoute)->withErrors(['google' => 'Respons Google tidak lengkap. Silakan coba lagi.']);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Exception) {
            return redirect()->route($loginRoute)->withErrors(['google' => 'Login Google gagal diverifikasi. Silakan coba lagi.']);
        }

        $googleId = $googleUser->getId();
        $email = $googleUser->getEmail();
        if (! is_string($googleId) || $googleId === '' || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ($googleUser->getRaw()['email_verified'] ?? false) !== true) {
            return redirect()->route($loginRoute)->withErrors(['google' => 'Akun Google harus memiliki email terverifikasi.']);
        }

        $linkedUser = User::where('google_id', $googleId)->first();
        if ($linkedUser) {
            if ($linkedUser->role !== $role) {
                return redirect()->route($loginRoute)->withErrors(['google' => 'Akun Google ini terdaftar untuk role lain. Pilih jenis akun yang sesuai.']);
            }

            Auth::login($linkedUser);
            $request->session()->regenerate();

            if ($role === 'employer') {
                $company = DB::table('companies')->where('owner_user_id', $linkedUser->id)->select('name', 'location')->first();
                if (! filled(trim((string) $linkedUser->full_name)) || ! filled(trim((string) $linkedUser->phone))
                    || ! filled(trim((string) $company?->name)) || ! filled(trim((string) $company?->location))) {
                    return redirect()->route('employer.setup');
                }
            }

            return redirect()->route($role === 'employer' ? 'employer.dashboard' : 'jobs.index');
        }

        $localUser = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
        if (! $localUser) {
            $request->session()->put('google_oauth_registration', [
                'id' => $googleId,
                'email' => strtolower($email),
                'name' => $googleUser->getName(),
                'role' => $role,
                'expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect()->route($loginRoute, ['mode' => 'register'])
                ->with('status', 'Email Google terverifikasi. Lengkapi data yang belum tersedia untuk membuat akun.');
        }

        if ($localUser->role !== $role) {
            return redirect()->route($loginRoute)->withErrors(['google' => 'Email ini terdaftar untuk role lain. Pilih jenis akun yang sesuai.']);
        }

        if ($localUser->google_id !== null) {
            return redirect()->route($loginRoute)->withErrors(['google' => 'Akun ini sudah terhubung dengan akun Google lain. Gunakan email dan kata sandi.']);
        }

        $request->session()->put('google_oauth_pending', [
            'id' => $googleId,
            'email' => strtolower($email),
            'role' => $role,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return redirect()->route($loginRoute)->with('status', 'Untuk menghubungkan Google, masuk sekali dengan kata sandi akun Anda.');
    }
}
