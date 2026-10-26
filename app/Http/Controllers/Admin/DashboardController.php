<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $counts = [
            'companies' => DB::table('companies')->count(),
            'active_jobs' => DB::table('job_posts')->where('status', 'active')->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count(),
            'applications' => DB::table('applications')->count(),
            'draft_articles' => DB::table('articles')->where('status', 'draft')->count(),
        ];
        $ownedCompanies = DB::table('companies')->where('owner_user_id', auth()->id())->count();
        $newCompaniesWithoutTrial = DB::table('companies')
            ->join('users as owners', 'companies.owner_user_id', '=', 'owners.id')
            ->where('owners.role', 'employer')
            ->where('companies.created_at', '>', now()->subDay())
            ->where('companies.created_at', '<=', now())
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('company_subscriptions')
                ->whereColumn('company_subscriptions.company_id', 'companies.id'))
            ->count();

        $recentApplications = DB::table('applications')
            ->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->select('applications.id', 'applications.status', 'applications.applied_at', 'jobseeker_profiles.full_name', 'job_posts.title as job_title', 'companies.name as company_name')
            ->orderByDesc('applications.applied_at')->limit(5)->get();

        $recentJobs = DB::table('job_posts')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->select('job_posts.id', 'job_posts.title', 'job_posts.status', 'job_posts.created_at', 'companies.name as company_name')
            ->orderByDesc('job_posts.created_at')->limit(5)->get();

        return view('admin.dashboard', compact('counts', 'ownedCompanies', 'newCompaniesWithoutTrial', 'recentApplications', 'recentJobs'));
    }
}
