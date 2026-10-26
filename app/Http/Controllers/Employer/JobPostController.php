<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JobPostController extends Controller
{
    public function index(Request $request): View
    {
        $company = DB::table('companies')->where('owner_user_id', $request->user()->id)->first();
        $jobs = DB::table('job_posts')->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->where('companies.owner_user_id', $request->user()->id)
            ->where('job_posts.posted_by', $request->user()->id)
            ->select('job_posts.id', 'job_posts.title', 'job_posts.location', 'job_posts.status', 'job_posts.expires_at', 'job_posts.created_at')
            ->orderByDesc('job_posts.created_at')->paginate(15);

        return view('employer.job-posts.index', compact('company', 'jobs'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $company = DB::table('companies')->where('owner_user_id', $request->user()->id)->first();
        if ($company === null) {
            return redirect()->route('employer.setup');
        }

        $subscription = $this->activeSubscription($company->id);
        $hasQuota = $subscription !== null && $subscription->quota_total !== null
            && DB::table('job_posts')->where('company_id', $company->id)
                ->where('status', 'active')->whereNotNull('subscription_id')
                ->where('expires_at', '>', now())->count() < $subscription->quota_total;

        return view('employer.job-posts.create', compact('company', 'subscription', 'hasQuota'));
    }

    public function show(Request $request, string $id): View
    {
        $job = $this->findJob($request, $id);
        $applicationCount = DB::table('applications')->where('job_posts_id', $job->id)->count();
        $subscription = $this->activeSubscription($job->company_id);
        $activeCount = DB::table('job_posts')->where('company_id', $job->company_id)
            ->where('status', 'active')->whereNotNull('subscription_id')
            ->where('expires_at', '>', now())->count();
        $hasValidExpiry = $subscription !== null && $job->expires_at !== null
            && Carbon::parse($job->expires_at)->isFuture()
            && Carbon::parse($job->expires_at)->lessThanOrEqualTo($subscription->expires_at);
        $hasQuota = $subscription !== null && $subscription->quota_total !== null
            && $activeCount < $subscription->quota_total;

        return view('employer.job-posts.show', compact('job', 'applicationCount', 'subscription', 'hasValidExpiry', 'hasQuota'));
    }

    public function edit(Request $request, string $id): View
    {
        $job = $this->findJob($request, $id);
        $subscription = $this->activeSubscription($job->company_id);

        return view('employer.job-posts.edit', compact('job', 'subscription'));
    }

    private function activeSubscription(string $companyId): ?object
    {
        $subscription = DB::table('company_subscriptions')->where('company_id', $companyId)
            ->where('status', 'active')->where('starts_at', '<=', now())
            ->where('expires_at', '>', now())->orderByDesc('starts_at')->first();

        if ($subscription !== null && $subscription->source === 'purchase'
            && ! DB::table('payment_transactions')->where('subscription_id', $subscription->id)
                ->where('status', 'paid')->exists()) {
            return null;
        }

        return $subscription;
    }

    private function findJob(Request $request, string $id): object
    {
        $job = DB::table('job_posts')->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->where('job_posts.id', $id)
            ->where('companies.owner_user_id', $request->user()->id)
            ->where('job_posts.posted_by', $request->user()->id)
            ->select('job_posts.*', 'companies.name as company_name')->first();
        abort_if($job === null, 404);

        return $job;
    }
}
