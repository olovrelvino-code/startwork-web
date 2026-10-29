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
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $applicants = DB::table('applications')
            ->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->select('applications.id', 'applications.status', 'applications.applied_at', 'applications.cv_file', 'jobseeker_profiles.full_name', 'companies.name as company_name', 'job_posts.title as job_title')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(function ($match) use ($search): void {
                $match->where('jobseeker_profiles.full_name', 'like', '%'.$search.'%')
                    ->orWhere('job_posts.title', 'like', '%'.$search.'%')
                    ->orWhere('companies.name', 'like', '%'.$search.'%')
                    ->orWhere('applications.status', 'like', '%'.$search.'%');
            }))
            ->orderByDesc('applications.applied_at')->orderByDesc('applications.id')
            ->paginate(10)->withQueryString();

        return view('admin.applicants.index', compact('applicants'));
    }
}
