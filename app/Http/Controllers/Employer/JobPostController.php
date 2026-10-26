<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('employer.job-posts.create', compact('company'));
    }

    public function show(Request $request, string $id): View
    {
        $job = $this->findJob($request, $id);
        $applicationCount = DB::table('applications')->where('job_posts_id', $job->id)->count();

        return view('employer.job-posts.show', compact('job', 'applicationCount'));
    }

    public function edit(Request $request, string $id): View
    {
        $job = $this->findJob($request, $id);

        return view('employer.job-posts.edit', compact('job'));
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
