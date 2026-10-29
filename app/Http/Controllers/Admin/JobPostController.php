<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'company' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $jobs = DB::table('job_posts')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->select('job_posts.id', 'job_posts.title', 'job_posts.status', 'job_posts.apply_url', 'job_posts.expires_at', 'job_posts.created_at', 'job_posts.posted_by', 'companies.name as company_name', 'companies.owner_user_id as company_owner_user_id')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('job_posts.status', $status))
            ->when($filters['company_id'] ?? null, fn ($query, $companyId) => $query->where('job_posts.company_id', $companyId))
            ->when($filters['company'] ?? null, fn ($query, $company) => $query->where('companies.name', 'like', '%'.trim($company).'%'))
            ->when($request->boolean('mine'), fn ($query) => $query->where('job_posts.posted_by', $request->user()->id))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->where('job_posts.created_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->where('job_posts.created_at', '<', Carbon::parse($date)->addDay()->startOfDay()))
            ->orderByDesc('job_posts.created_at')->orderByDesc('job_posts.id')
            ->paginate(10)->withQueryString();

        $selectedCompany = isset($filters['company_id'])
            ? DB::table('companies')->where('id', $filters['company_id'])->first(['id', 'name'])
            : null;

        return view('admin.job-posts.index', compact('jobs', 'selectedCompany'));
    }

    public function create(Request $request): View
    {
        $companies = DB::table('companies')->where('owner_user_id', $request->user()->id)
            ->select('id', 'name')->orderBy('name')->limit(10)->get();

        return view('admin.job-posts.create', compact('companies'));
    }

    public function companyOptions(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'in:owned,all'],
        ]);
        $search = trim($filters['q'] ?? '');
        $companies = DB::table('companies')
            ->when(($filters['scope'] ?? 'owned') === 'owned', fn ($query) => $query->where('owner_user_id', $request->user()->id))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', $search.'%'))
            ->select('id', 'name')->orderBy('name')->limit(10)->get();

        return response()->json(['data' => $companies]);
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
            ->orderByDesc('applications.applied_at')->orderByDesc('applications.id')->paginate(15);

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
