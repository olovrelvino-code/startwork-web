<?php

namespace App\Http\Controllers\Auth;

use App\GoogleRegistration;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        return view('auth.register', ['pendingGoogle' => GoogleRegistration::pending($request, 'jobseeker')]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $googleRegistration = $request->input('registration_method') === 'google';
        $pendingGoogle = $googleRegistration ? GoogleRegistration::pending($request, 'jobseeker') : null;
        if ($googleRegistration && $pendingGoogle === null) {
            throw ValidationException::withMessages(['email' => 'Sesi Google berakhir. Masuk dengan Google lagi.']);
        }

        $input = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', ...($googleRegistration ? [Rule::in([$pendingGoogle['email']])] : []), 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:255'],
            'password' => $googleRegistration ? ['missing'] : ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($input, $googleRegistration, $pendingGoogle): User {
            if ($googleRegistration && (User::where('google_id', $pendingGoogle['id'])->exists()
                || User::whereRaw('LOWER(email) = ?', [$pendingGoogle['email']])->exists())) {
                throw ValidationException::withMessages(['email' => 'Akun sudah terdaftar. Masuk dahulu untuk menghubungkan Google.']);
            }

            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'] ?? null,
                'password' => $googleRegistration ? null : Hash::make($input['password']),
            ]);

            if ($googleRegistration) {
                $user->google_id = $pendingGoogle['id'];
                $user->email_verified_at = now();
                $user->save();
            }

            return $user;
        });

        $request->session()->forget('google_oauth_registration');
        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        $intendedPath = parse_url((string) $request->session()->pull('url.intended'), PHP_URL_PATH) ?: '';
        if (preg_match('#\A/jobs/([0-9a-f-]{36})/apply\z#i', $intendedPath, $matches)) {
            return redirect()->route('jobs.show', $matches[1]);
        }

        return redirect(route('jobs.index', absolute: false));
    }
}
