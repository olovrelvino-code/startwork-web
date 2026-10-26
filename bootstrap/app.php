<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureEmployer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin' => EnsureAdmin::class, 'employer' => EnsureEmployer::class]);
        $middleware->redirectGuestsTo(fn (Request $request): string => match (true) {
            $request->routeIs('account.switch') => match ($request->route('role')) {
                'admin' => route('admin.login'),
                'employer' => route('employer.login'),
                'jobseeker' => route('login'),
            },
            $request->is('admin', 'admin/*') => route('admin.login'),
            $request->is('employer', 'employer/*') => route('employer.login'),
            default => route('login'),
        });
        $middleware->redirectUsersTo(function (Request $request): string {
            $requestedRole = match (true) {
                $request->routeIs('admin.login', 'admin.login.store') => 'admin',
                $request->routeIs('employer.login', 'employer.login.store') => 'employer',
                $request->routeIs('login', 'login.store') => 'jobseeker',
                default => null,
            };

            $currentRole = $request->user()->role;
            if ($requestedRole !== null && $requestedRole !== $currentRole) {
                return route('account.switch', ['role' => $requestedRole]);
            }

            return match ($currentRole) {
                'admin' => route('admin.dashboard'),
                'employer' => route('employer.dashboard'),
                'jobseeker' => route('dashboard'),
                default => abort(403),
            };
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
