<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JobPostController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:active,inactive,expired'],
            'mine' => ['nullable', 'boolean'],
            'company_id' => ['nullable', 'uuid'],
        ]);

        $jobs = DB::table('job_posts')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->select('job_posts.id', 'job_posts.title', 'job_posts.status', 'job_posts.apply_url', 'job_posts.expires_at', 'job_posts.created_at', 'job_posts.posted_by', 'companies.name as company_name', 'companies.owner_user_id as company_owner_user_id')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('job_posts.status', $status))
            ->when($filters['company_id'] ?? null, fn ($query, $companyId) => $query->where('job_posts.company_id', $companyId))
            ->when($request->boolean('mine'), fn ($query) => $query->where('job_posts.posted_by', $request->user()->id))
            ->orderByDesc('job_posts.created_at')->paginate(15)->withQueryString();

        $companies = DB::table('companies')->select('id', 'name')->orderBy('name')->get();

        return view('admin.job-posts.index', compact('jobs', 'companies'));
    }

    public function create(Request $request): View
    {
        $companies = DB::table('companies')->where('owner_user_id', $request->user()->id)
            ->select('id', 'name')->orderBy('name')->get();

        return view('admin.job-posts.create', compact('companies'));
    }

    public function show(Request $request, string $id): View
    {
        $job = $this->findJob($id);
        $isOwned = $job->posted_by === $request->user()->id && $job->company_owner_user_id === $request->user()->id;
        $isEmployerJob = $job->poster_role === 'employer' && $job->company_owner_user_id === $job->posted_by;
        $applicants = DB::table('applications')
            ->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->where('applications.job_posts_id', $id)
            ->select('applications.id', 'applications.status', 'applications.applied_at', 'jobseeker_profiles.full_name')
            ->orderByDesc('applications.applied_at')->paginate(10);

        return view('admin.job-posts.show', compact('job', 'isOwned', 'isEmployerJob', 'applicants'));
    }

    public function edit(Request $request, string $id): View
    {
        $job = $this->findJob($id);
        abort_unless(
            ($job->posted_by === $request->user()->id && $job->company_owner_user_id === $request->user()->id)
            || ($job->poster_role === 'employer' && $job->company_owner_user_id === $job->posted_by),
            404
        );

        return view('admin.job-posts.edit', compact('job'));
    }

    private function findJob(string $id): object
    {
        $job = DB::table('job_posts')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->join('users as posters', 'job_posts.posted_by', '=', 'posters.id')
            ->where('job_posts.id', $id)
            ->select('job_posts.*', 'companies.name as company_name', 'companies.owner_user_id as company_owner_user_id', 'posters.role as poster_role')
            ->first();
        abort_if($job === null, 404);

        return $job;
    }
}
