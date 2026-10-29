<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
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

        $request->session()->regenerate();

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
