<?php

namespace App\Http\Controllers\Auth;

use App\GoogleRegistration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        if ($request->query('mode') === 'register') {
            return view('auth.register', ['pendingGoogle' => GoogleRegistration::pending($request, 'jobseeker')]);
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $role = match (true) {
            $request->routeIs('admin.login.store') => 'admin',
            $request->routeIs('employer.login.store') => 'employer',
            default => 'jobseeker',
        };

        $request->authenticate($role);

        $pendingGoogle = $request->session()->pull('google_oauth_pending');
        $user = $request->user();
        if (is_array($pendingGoogle)
            && ($pendingGoogle['role'] ?? null) === $role
            && ($pendingGoogle['expires_at'] ?? 0) >= now()->timestamp
            && is_string($pendingGoogle['id'] ?? null)
            && strcasecmp($user->email, $pendingGoogle['email'] ?? '') === 0
            && $user->google_id === null
            && ! User::where('google_id', $pendingGoogle['id'])->exists()) {
            $user->google_id = $pendingGoogle['id'];
            $user->save();
        }

        $request->session()->regenerate();
        $request->session()->forget('google_oauth_registration');

        $authenticatedRole = $request->user()->role;
        $destination = match ($authenticatedRole) {
            'admin' => route('admin.dashboard', absolute: false),
            'employer' => route('employer.dashboard', absolute: false),
            'jobseeker' => route('jobs.index', absolute: false),
        };

        $intendedPath = parse_url((string) $request->session()->get('url.intended'), PHP_URL_PATH) ?: '';
        if (($authenticatedRole !== 'admin' && str_starts_with($intendedPath, '/admin/'))
            || ($authenticatedRole !== 'employer' && str_starts_with($intendedPath, '/employer/'))) {
            $request->session()->forget('url.intended');
        }

        if ($authenticatedRole === 'jobseeker') {
            $request->session()->forget('url.intended');

            if (preg_match('#\A/jobs/([0-9a-f-]{36})/apply\z#i', $intendedPath, $matches)) {
                return redirect()->route('jobs.show', $matches[1]);
            }

            return redirect($destination);
        }

        return redirect()->intended($destination);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $destination = match ($request->input('next')) {
            'admin' => route('admin.login', absolute: false),
            'employer' => route('employer.login', absolute: false),
            'jobseeker' => route('login', absolute: false),
            default => '/',
        };

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect($destination);
    }
}
