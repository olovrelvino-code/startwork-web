<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApplicantController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'uuid'],
        ]);

        $applicants = DB::table('applications')
            ->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->select('applications.id', 'applications.status', 'applications.applied_at', 'applications.cv_file', 'jobseeker_profiles.full_name', 'companies.name as company_name', 'job_posts.title as job_title')
            ->when($filters['name'] ?? null, fn ($query, $name) => $query->where('jobseeker_profiles.full_name', 'like', '%'.$name.'%'))
            ->when($filters['company_id'] ?? null, fn ($query, $companyId) => $query->where('companies.id', $companyId))
            ->orderByDesc('applications.applied_at')->paginate(15)->withQueryString();
        $companies = DB::table('companies')->select('id', 'name')->orderBy('name')->get();

        return view('admin.applicants.index', compact('applicants', 'companies'));
    }
}
