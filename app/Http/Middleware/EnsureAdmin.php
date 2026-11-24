<?php

namespace App\Http\Middleware;

use App\SubscriptionPurchases;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function __construct(private SubscriptionPurchases $purchases) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $this->purchases->activateDue();

        return $next($request);
    }
}
