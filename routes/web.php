<?php

use App\Http\Controllers\Admin\ApplicantController as AdminApplicantController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\JobPostController as AdminJobPostController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Employer\ApplicationController as EmployerApplicationController;
use App\Http\Controllers\Employer\CompanyController as EmployerCompanyController;
use App\Http\Controllers\Employer\DashboardController as EmployerDashboardController;
use App\Http\Controllers\Employer\JobPostController as EmployerJobPostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicArticleController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $activeJobs = DB::table('job_posts')->where('job_posts.status', 'active')
        ->where(fn ($query) => $query->whereNull('job_posts.expires_at')->orWhere('job_posts.expires_at', '>', now()));
    $jobs = (clone $activeJobs)->join('companies', 'job_posts.company_id', '=', 'companies.id')
        ->select('job_posts.id', 'job_posts.title', 'job_posts.location', 'job_posts.job_type', 'job_posts.work_type', 'companies.name as company_name', 'companies.logo as company_logo', 'companies.id as company_id')
        ->orderByDesc('job_posts.created_at')->orderBy('job_posts.id')->limit(6)->get();
    $categories = (clone $activeJobs)->whereNotNull('category')->where('category', '!=', '')
        ->select('category')->selectRaw('COUNT(*) as jobs_count')->groupBy('category')
        ->orderByDesc('jobs_count')->orderBy('category')->limit(8)->get();
    $companyJobs = (clone $activeJobs)->select('company_id')->selectRaw('COUNT(*) as open_jobs')->groupBy('company_id');
    $companies = DB::table('companies')->joinSub($companyJobs, 'open_jobs', fn ($join) => $join->on('companies.id', '=', 'open_jobs.company_id'))
        ->select('companies.id', 'companies.name', 'companies.location', 'companies.logo', 'open_jobs.open_jobs')
        ->orderByDesc('open_jobs.open_jobs')->orderBy('companies.name')->limit(12)->get();

    return view('welcome', [
        'jobs' => $jobs,
        'categories' => $categories,
        'companies' => $companies,
        'activeJobCount' => (clone $activeJobs)->count(),
        'companyCount' => DB::table('companies')->count(),
    ]);
});

Route::get('/loker', function () {
    $active = DB::table('job_posts')->where('status', 'active')
        ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));

    return view('jobseeker.jobs.index', [
        'categories' => (clone $active)->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
        'jobTypes' => (clone $active)->whereNotNull('job_type')->distinct()->orderBy('job_type')->pluck('job_type'),
        'workTypes' => (clone $active)->whereNotNull('work_type')->distinct()->orderBy('work_type')->pluck('work_type'),
    ]);
})->name('jobs.index');
Route::get('/loker/{id}', fn (string $id) => redirect()->route('jobs.show', $id));
Route::view('/perusahaan', 'jobseeker.companies.index')->name('companies.index');
Route::get('/perusahaan/{id}', function (string $id) {
    abort_unless(DB::table('companies')->where('id', $id)->exists(), 404);

    return view('jobseeker.companies.show', compact('id'));
})->name('companies.show');
Route::view('/about-us', 'jobseeker.about')->name('about');
Route::view('/faqs', 'jobseeker.faqs')->name('faqs');
Route::redirect('/blog', '/articles')->name('blog');
Route::get('/blog/{slug}', fn (string $slug) => redirect()->route('articles.show', $slug));

Route::get('/articles', [PublicArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{slug}/cover', [PublicArticleController::class, 'cover'])->name('articles.cover');
Route::get('/articles/{slug}/images/{image}', [PublicArticleController::class, 'image'])->name('articles.image');
Route::get('/articles/{slug}', [PublicArticleController::class, 'show'])->name('articles.show');

Route::get('/dashboard', function () {
    if (request()->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    if (request()->user()->role === 'employer') {
        return redirect()->route('employer.dashboard');
    }

    abort_unless(request()->user()->role === 'jobseeker', 403);

    return redirect()->route('jobs.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('/profil', function () {
        abort_unless(request()->user()->role === 'jobseeker', 403);

        return view('jobseeker.profile');
    })->name('jobseeker.profile');
    Route::get('/profil/lamaran', function () {
        abort_unless(request()->user()->role === 'jobseeker', 403);

        return view('jobseeker.applications');
    })->name('jobseeker.applications');
    Route::get('/jobs/{id}/apply', function (string $id) {
        abort_unless(request()->user()->role === 'jobseeker', 403);

        return redirect()->route('jobs.show', $id);
    })->name('jobs.apply-continue');
});

Route::middleware('auth')->get('/account/switch/{role}', function (string $role) {
    return view('auth.switch-account', [
        'role' => $role,
        'user' => request()->user(),
    ]);
})->where('role', 'admin|employer|jobseeker')->name('account.switch');

Route::middleware('guest')->get('/employer/register', function () {
    return view('employer.register');
})->name('employer.register');

Route::middleware('guest')->get('/employer/login', function () {
    return view('employer.login');
})->name('employer.login');

Route::middleware('guest')->post('/employer/login', [AuthenticatedSessionController::class, 'store'])->name('employer.login.store');

Route::middleware('guest')->get('/admin/login', function () {
    return view('auth.admin-login');
})->name('admin.login');

Route::middleware('guest')->post('/admin/login', [AuthenticatedSessionController::class, 'store'])->name('admin.login.store');

Route::middleware(['auth', 'employer'])->prefix('employer')->name('employer.')->group(function (): void {
    Route::redirect('/', '/employer/dashboard')->name('home');
    Route::get('/dashboard', EmployerDashboardController::class)->name('dashboard');
    Route::get('/setup', [EmployerCompanyController::class, 'setup'])->name('setup');
    Route::get('/company', EmployerCompanyController::class)->name('company');
    Route::get('/job-posts', [EmployerJobPostController::class, 'index'])->name('job-posts.index');
    Route::get('/job-posts/create', [EmployerJobPostController::class, 'create'])->name('job-posts.create');
    Route::get('/job-posts/{id}', [EmployerJobPostController::class, 'show'])->name('job-posts.show');
    Route::get('/job-posts/{id}/edit', [EmployerJobPostController::class, 'edit'])->name('job-posts.edit');
    Route::get('/applications', [EmployerApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{id}', [EmployerApplicationController::class, 'show'])->name('applications.show');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::redirect('/', '/admin/dashboard')->name('home');
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

    Route::get('/companies', [AdminCompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/{id}', [AdminCompanyController::class, 'show'])->name('companies.show');

    Route::get('/job-posts', [AdminJobPostController::class, 'index'])->name('job-posts.index');
    Route::get('/job-posts/create', [AdminJobPostController::class, 'create'])->name('job-posts.create');
    Route::get('/job-posts/company-options', [AdminJobPostController::class, 'companyOptions'])->name('job-posts.company-options');
    Route::get('/job-posts/{id}', [AdminJobPostController::class, 'show'])->name('job-posts.show');
    Route::get('/job-posts/{id}/edit', [AdminJobPostController::class, 'edit'])->name('job-posts.edit');

    Route::get('/applicants', [AdminApplicantController::class, 'index'])->name('applicants.index');

    Route::get('/articles', [AdminArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/create', [AdminArticleController::class, 'create'])->name('articles.create');
    Route::get('/articles/{id}/edit', [AdminArticleController::class, 'edit'])->name('articles.edit');

    Route::get('/packages', AdminPackageController::class)->name('packages.index');
});

Route::get('/jobs/{id}', function (string $id) {
    $job = DB::table('job_posts')
        ->join('companies', 'job_posts.company_id', '=', 'companies.id')
        ->where('job_posts.id', $id)
        ->where('job_posts.status', 'active')
        ->where(function ($q) {
            $q->whereNull('job_posts.expires_at')->orWhere('job_posts.expires_at', '>', now());
        })
        ->select('job_posts.*', 'companies.name as company_name', 'companies.logo as company_logo')
        ->first();
    abort_if($job === null, 404, 'Lowongan tidak ditemukan');

    return view('job-detail', ['job' => $job]);
})->name('jobs.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
