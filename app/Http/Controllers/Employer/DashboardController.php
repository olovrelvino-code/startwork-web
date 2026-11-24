<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\SubscriptionPurchases;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SubscriptionPurchases $purchases): View|RedirectResponse
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $search = trim($filters['q'] ?? '');
        $company = DB::table('companies')->where('owner_user_id', $request->user()->id)->first();
        if ($company === null || ($request->user()->google_id !== null
            && (! filled(trim((string) $company->name)) || ! filled(trim((string) $company->location))
                || ! filled(trim((string) $request->user()->full_name))
                || ! filled(trim((string) $request->user()->phone))))) {
            return redirect()->route('employer.setup');
        }
        $purchases->activateForCompany($company->id);
        $trial = DB::table('company_subscriptions')->where('company_id', $company->id)
            ->where('source', 'admin_trial')->orderByDesc('starts_at')->first();
        $trialIsActive = $trial !== null && $trial->status === 'active'
            && Carbon::parse($trial->starts_at)->lessThanOrEqualTo(now()) && Carbon::parse($trial->expires_at)->isFuture();
        $activePackage = null;
        $hasOpenPurchase = false;
        $hasPendingActivation = false;
        if (! $trialIsActive) {
            $activePackage = DB::table('company_subscriptions')
                ->join('payment_transactions', 'payment_transactions.subscription_id', '=', 'company_subscriptions.id')
                ->join('packages', 'packages.id', '=', 'company_subscriptions.package_id')
                ->where('company_subscriptions.company_id', $company->id)->where('company_subscriptions.source', 'purchase')
                ->where('company_subscriptions.status', 'active')->where('company_subscriptions.starts_at', '<=', now())
                ->where('company_subscriptions.expires_at', '>', now())->where('payment_transactions.status', 'paid')
                ->first(['packages.name', 'company_subscriptions.expires_at']);
            if ($activePackage === null) {
                $hasPendingActivation = DB::table('company_subscriptions')->where('company_id', $company->id)
                    ->where('source', 'purchase')->where('status', 'pending_activation')->exists();
                $hasOpenPurchase = DB::table('company_subscriptions')
                    ->join('payment_transactions', 'payment_transactions.subscription_id', '=', 'company_subscriptions.id')
                    ->where('company_subscriptions.company_id', $company->id)->where('company_subscriptions.source', 'purchase')
                    ->where('company_subscriptions.status', 'pending')
                    ->whereIn('payment_transactions.status', ['pending_payment', 'waiting_verification'])->exists();
            }
        }

        $latestEndedSubscription = DB::table('company_subscriptions')
            ->where('company_id', $company->id)
            ->whereIn('status', ['expired', 'revoked'])
            ->orderByDesc('ended_at')
            ->orderByDesc('expires_at')
            ->first();

        $ownJobs = DB::table('job_posts')->where('company_id', $company->id)
            ->where('posted_by', $request->user()->id);
        $counts = [
            'jobs' => (clone $ownJobs)->count(),
            'active_jobs' => (clone $ownJobs)->where('status', 'active')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->count(),
        ];
        $ownApplications = DB::table('applications')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->where('job_posts.company_id', $company->id)
            ->where('job_posts.posted_by', $request->user()->id);
        $counts['applications'] = (clone $ownApplications)->count();

        $matchesJobTitle = static fn ($query) => $query->whereRaw('LOWER(job_posts.title) LIKE LOWER(?)', ['%'.$search.'%']);
        $matchesApplicantName = static fn ($query) => $query->whereRaw('LOWER(jobseeker_profiles.full_name) LIKE LOWER(?)', ['%'.$search.'%']);
        $hasMatchingJobs = $search !== '' && (clone $ownJobs)->where($matchesJobTitle)->exists();
        $hasMatchingApplications = $search !== '' && (clone $ownApplications)->where($matchesApplicantName)->exists();
        // A query matching only one dataset leaves the other list unchanged.
        $filterJobs = $search !== '' && ($hasMatchingJobs || ! $hasMatchingApplications);
        $filterApplications = $search !== '' && ($hasMatchingApplications || ! $hasMatchingJobs);

        $jobs = (clone $ownJobs)->when($filterJobs, fn ($query) => $query->where($matchesJobTitle))
            ->select('id', 'title', 'status', 'created_at')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(10, ['*'], 'jobs_page')->withQueryString();
        $applications = (clone $ownApplications)
            ->when($filterApplications, fn ($query) => $query->where($matchesApplicantName))
            ->select('applications.id', 'applications.status', 'job_posts.title as job_title', 'jobseeker_profiles.full_name')
            ->orderByDesc('applications.applied_at')->orderByDesc('applications.id')
            ->paginate(10, ['*'], 'applications_page')->withQueryString();

        return view('employer.dashboard', compact('company', 'trial', 'trialIsActive', 'activePackage', 'hasOpenPurchase', 'hasPendingActivation', 'latestEndedSubscription', 'counts', 'jobs', 'applications', 'search', 'filterJobs', 'filterApplications'));
    }
}
