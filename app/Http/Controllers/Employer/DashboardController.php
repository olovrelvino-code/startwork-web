<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $search = trim($filters['q'] ?? '');
        $company = DB::table('companies')->where('owner_user_id', $request->user()->id)->first();
        if ($company === null) {
            return redirect()->route('employer.setup');
        }
        $trial = DB::table('company_subscriptions')->where('company_id', $company->id)
            ->where('source', 'admin_trial')->orderByDesc('starts_at')->first();

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

        return view('employer.dashboard', compact('company', 'trial', 'counts', 'jobs', 'applications', 'search', 'filterJobs', 'filterApplications'));
    }
}
