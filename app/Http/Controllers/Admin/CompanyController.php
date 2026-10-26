<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'filter' => ['nullable', 'in:all,new,no_trial,trial_active,trial_expired'],
        ]);
        $now = now();
        $subscriptionSummary = DB::table('company_subscriptions')->select('company_id')
            ->selectRaw('COUNT(*) as subscription_count')
            ->selectRaw("MAX(CASE WHEN source = 'admin_trial' THEN expires_at END) as trial_expires_at")
            ->groupBy('company_id');
        $companies = DB::table('companies')
            ->leftJoin('users as owners', 'companies.owner_user_id', '=', 'owners.id')
            ->leftJoinSub($subscriptionSummary, 'subscriptions', fn ($join) => $join->on('subscriptions.company_id', '=', 'companies.id'))
            ->select('companies.id', 'companies.name', 'companies.location', 'companies.owner_user_id', 'companies.created_by', 'companies.logo', 'companies.created_at', 'owners.role as owner_role', 'subscriptions.subscription_count', 'subscriptions.trial_expires_at')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('companies.name', 'like', '%'.$search.'%'))
            ->when($filters['filter'] ?? null, function ($query, $filter) use ($now): void {
                match ($filter) {
                    'new' => $query->where('companies.created_at', '>', $now->copy()->subDay())
                        ->where('companies.created_at', '<=', $now),
                    'no_trial' => $query->where('owners.role', 'employer')->whereNull('subscriptions.trial_expires_at'),
                    'trial_active' => $query->where('subscriptions.trial_expires_at', '>', $now),
                    'trial_expired' => $query->where('subscriptions.trial_expires_at', '<=', $now),
                    default => null,
                };
            })
            ->orderByDesc('companies.created_at')->orderByDesc('companies.id')
            ->paginate(15)->withQueryString();

        return view('admin.companies.index', compact('companies'));
    }

    public function show(Request $request, string $id): View
    {
        $company = DB::table('companies')->leftJoin('users as owners', 'companies.owner_user_id', '=', 'owners.id')
            ->where('companies.id', $id)->select('companies.*', 'owners.role as owner_role')->first();
        abort_if($company === null, 404);

        $jobs = DB::table('job_posts')->where('company_id', $id)
            ->select('id', 'title', 'status', 'posted_by', 'created_at', 'expires_at')
            ->orderByDesc('created_at')->paginate(10);
        $canManage = $company->owner_user_id === $request->user()->id || $company->owner_role === 'employer';
        $trial = DB::table('company_subscriptions')->where('company_id', $id)
            ->where('source', 'admin_trial')->orderByDesc('starts_at')->first();
        $hasSubscriptionHistory = DB::table('company_subscriptions')->where('company_id', $id)->exists();
        $canGrantTrial = $company->owner_role === 'employer' && ! $hasSubscriptionHistory;
        $canDelete = $jobs->total() === 0 && ! $hasSubscriptionHistory;
        $packages = $canGrantTrial
            ? DB::table('packages')->where('is_active', true)->where('quota', '>', 0)->orderBy('name')->get()
            : collect();

        return view('admin.companies.show', compact('company', 'jobs', 'canManage', 'canDelete', 'trial', 'canGrantTrial', 'packages'));
    }
}
