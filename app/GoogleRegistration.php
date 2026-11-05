<?php

namespace App;

use Illuminate\Http\Request;

class GoogleRegistration
{
    /** @return array{id: string, email: string, role: string, name: ?string, expires_at: int}|null */
    public static function pending(Request $request, string $role): ?array
    {
        $pending = $request->session()->get('google_oauth_registration');

        if (! is_array($pending) || ($pending['role'] ?? null) !== $role
            || ! is_string($pending['id'] ?? null) || $pending['id'] === ''
            || ! is_string($pending['email'] ?? null) || ! filter_var($pending['email'], FILTER_VALIDATE_EMAIL)
            || ! is_int($pending['expires_at'] ?? null) || $pending['expires_at'] < now()->timestamp) {
            return null;
        }

        return $pending;
    }
}
