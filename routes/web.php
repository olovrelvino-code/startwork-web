<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/jobs/{id}', function (string $id) {
    $job = DB::table('job_posts')
        ->join('companies', 'job_posts.company_id', '=', 'companies.id')
        ->where('job_posts.id', $id)
        ->where('job_posts.status', 'active')
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
