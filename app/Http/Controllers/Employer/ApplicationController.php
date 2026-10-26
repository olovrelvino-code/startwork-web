<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['job_id' => ['nullable', 'uuid']]);
        $jobs = DB::table('job_posts')->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->where('companies.owner_user_id', $request->user()->id)
            ->where('job_posts.posted_by', $request->user()->id)
            ->select('job_posts.id', 'job_posts.title')->orderBy('job_posts.title')->get();
        $applications = DB::table('applications')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->where('companies.owner_user_id', $request->user()->id)
            ->where('job_posts.posted_by', $request->user()->id)
            ->when($filters['job_id'] ?? null, fn ($query, $id) => $query->where('job_posts.id', $id))
            ->select('applications.id', 'applications.status', 'applications.applied_at', 'job_posts.title as job_title', 'jobseeker_profiles.full_name')
            ->orderByDesc('applications.applied_at')->orderByDesc('applications.id')
            ->paginate(15)->withQueryString();

        return view('employer.applications.index', compact('applications', 'jobs'));
    }

    public function show(Request $request, string $id): View
    {
        $application = DB::table('applications')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->where('applications.id', $id)
            ->where('companies.owner_user_id', $request->user()->id)
            ->where('job_posts.posted_by', $request->user()->id)
            ->select('applications.id', 'applications.status', 'applications.applied_at',
                DB::raw('CASE WHEN applications.cv_file IS NULL THEN 0 ELSE 1 END as has_cv'),
                'job_posts.id as job_id', 'job_posts.title as job_title',
                'jobseeker_profiles.full_name', 'jobseeker_profiles.domicile', 'jobseeker_profiles.birth_date',
                'jobseeker_profiles.gender', 'jobseeker_profiles.last_education', 'jobseeker_profiles.portfolio_link')
            ->first();
        abort_if($application === null, 404);

        return view('employer.applications.show', compact('application'));
    }
}
