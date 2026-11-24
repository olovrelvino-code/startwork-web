<?php

namespace App\Http\Middleware;

use App\SubscriptionPurchases;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployer
{
    public function __construct(private SubscriptionPurchases $purchases) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role === 'employer', 403);

        $companyId = DB::table('companies')->where('owner_user_id', $request->user()->id)->value('id');
        if ($companyId !== null) {
            $this->purchases->activateForCompany($companyId);
        }

        return $next($request);
    }
}
