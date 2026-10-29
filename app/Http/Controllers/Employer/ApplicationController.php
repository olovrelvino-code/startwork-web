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
        $filters = $request->validate([
            'job_id' => ['nullable', 'uuid'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);
        $applications = DB::table('applications')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->where('companies.owner_user_id', $request->user()->id)
            ->where('job_posts.posted_by', $request->user()->id)
            ->when($filters['job_id'] ?? null, fn ($query, $id) => $query->where('job_posts.id', $id))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(function ($match) use ($search): void {
                $match->where('jobseeker_profiles.full_name', 'like', '%'.$search.'%')
                    ->orWhere('job_posts.title', 'like', '%'.$search.'%')
                    ->orWhere('applications.status', 'like', '%'.$search.'%');
            }))
            ->select('applications.id', 'applications.status', 'applications.applied_at', 'job_posts.title as job_title', 'jobseeker_profiles.full_name')
            ->orderByDesc('applications.applied_at')->orderByDesc('applications.id')
            ->paginate(15)->withQueryString();

        return view('employer.applications.index', compact('applications'));
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
            ->select('applications.id', 'applications.profile_id', 'applications.status', 'applications.applied_at',
                DB::raw('CASE WHEN applications.cv_file IS NULL THEN 0 ELSE 1 END as has_cv'),
                'job_posts.id as job_id', 'job_posts.title as job_title',
                'jobseeker_profiles.full_name', 'jobseeker_profiles.domicile', 'jobseeker_profiles.birth_date',
                'jobseeker_profiles.gender', 'jobseeker_profiles.last_education', 'jobseeker_profiles.portfolio_link')
            ->first();
        abort_if($application === null, 404);

        $workExperiences = DB::table('work_experiences')->where('profile_id', $application->profile_id)
            ->select('job_title', 'company', 'job_type', 'start_date', 'end_date', 'key_responsibilities')
            ->orderByDesc('start_date')->get();
        $organizations = DB::table('organization_experiences')->where('profile_id', $application->profile_id)
            ->select('organization_name', 'role', 'start_date', 'end_date', 'description')
            ->orderByDesc('start_date')->get();
        $skills = DB::table('skills')->where('profile_id', $application->profile_id)
            ->select('name')->orderBy('name')->get();
        $certificates = DB::table('certificates')->where('profile_id', $application->profile_id)
            ->select('name')->orderBy('name')->get();

        return view('employer.applications.show', compact('application', 'workExperiences', 'organizations', 'skills', 'certificates'));
    }
}
