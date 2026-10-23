<?php

use App\Http\Controllers\Admin\ApplicantController as AdminApplicantController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\JobPostController as AdminJobPostController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    if (request()->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::redirect('/', '/admin/dashboard')->name('home');
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

    Route::get('/companies', [AdminCompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/{id}', [AdminCompanyController::class, 'show'])->name('companies.show');

    Route::get('/job-posts', [AdminJobPostController::class, 'index'])->name('job-posts.index');
    Route::get('/job-posts/create', [AdminJobPostController::class, 'create'])->name('job-posts.create');
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
