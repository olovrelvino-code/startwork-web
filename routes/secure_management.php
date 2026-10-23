<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Route::middleware(['web', 'auth'])->prefix('admin')->group(function (): void {
    $ownedCompany = static function (Request $request, string $id): object {
        abort_unless($request->user()->role === 'admin', 403);
        $company = DB::table('companies')->where('id', $id)
            ->where('owner_user_id', $request->user()->id)->first();
        abort_if($company === null, 404, 'Perusahaan admin tidak ditemukan');

        return $company;
    };

    Route::get('/companies', function (Request $request) {
        abort_unless($request->user()->role === 'admin', 403);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:255']]);
        $query = DB::table('companies');
        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }
        $companies = $query->orderBy('name')->get();

        return response()->json(['total' => $companies->count(), 'data' => $companies]);
    });

    Route::get('/companies/{id}', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'admin', 403);
        $company = DB::table('companies')->where('id', $id)->first();
        abort_if($company === null, 404);
        $jobs = DB::table('job_posts')->where('company_id', $id)->orderByDesc('created_at')->get();

        return response()->json(['data' => $company, 'job_posts' => $jobs]);
    });

    Route::get('/companies/{id}/job-posts', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'admin', 403);
        abort_unless(DB::table('companies')->where('id', $id)->exists(), 404);
        $jobs = DB::table('job_posts')->where('company_id', $id)->orderByDesc('created_at')->get();

        return response()->json(['total' => $jobs->count(), 'data' => $jobs]);
    });

    Route::put('/companies/{id}', function (Request $request, string $id) use ($ownedCompany) {
        $ownedCompany($request, $id);
        $input = $request->validate([
            'id' => ['missing'], 'owner_user_id' => ['missing'], 'created_by' => ['missing'], 'logo' => ['missing'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'contact_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);
        $input['updated_at'] = now();
        DB::table('companies')->where('id', $id)->update($input);

        return response()->json(['data' => DB::table('companies')->where('id', $id)->first()]);
    });

    Route::delete('/companies/{id}', function (Request $request, string $id) use ($ownedCompany) {
        $ownedCompany($request, $id);
        abort_if(DB::table('job_posts')->where('company_id', $id)->exists(), 409, 'Hapus lowongan terlebih dahulu');
        abort_if(DB::table('company_subscriptions')->where('company_id', $id)->exists(), 409, 'Perusahaan memiliki riwayat subscription');
        DB::table('companies')->where('id', $id)->delete();

        return response()->noContent();
    });

    Route::delete('/job-posts/{id}', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'admin', 403);
        $job = DB::table('job_posts')->where('id', $id)->where('posted_by', $request->user()->id)
            ->whereIn('company_id', DB::table('companies')->where('owner_user_id', $request->user()->id)->select('id'))->first();
        abort_if($job === null, 404);
        abort_if($job->status === 'active', 409, 'Ubah lowongan menjadi inactive dahulu');
        abort_if(DB::table('applications')->where('job_posts_id', $id)->exists(), 409, 'Lowongan memiliki lamaran');
        DB::table('job_posts')->where('id', $id)->delete();

        return response()->noContent();
    });

    Route::get('/applicants/{id}/cv', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'admin', 403);
        $cv = DB::table('applications')->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->where('applications.id', $id)->select('applications.cv_file', 'jobseeker_profiles.cv_filename')->first();
        abort_if($cv === null || empty($cv->cv_file) || ! Storage::disk('local')->exists($cv->cv_file), 404);

        return Storage::disk('local')->download($cv->cv_file, basename($cv->cv_filename ?: $cv->cv_file));
    });
});

Route::middleware(['web', 'auth'])->prefix('employer')->group(function (): void {
    $ownCompany = static function (Request $request): object {
        abort_unless($request->user()->role === 'employer', 403);
        $company = DB::table('companies')->where('owner_user_id', $request->user()->id)->first();
        abort_if($company === null, 404, 'Perusahaan employer belum dibuat');

        return $company;
    };

    Route::put('/company', function (Request $request) use ($ownCompany) {
        $company = $ownCompany($request);
        $input = $request->validate([
            'id' => ['missing'], 'owner_user_id' => ['missing'], 'created_by' => ['missing'], 'logo' => ['missing'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'contact_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);
        $input['updated_at'] = now();
        DB::table('companies')->where('id', $company->id)->update($input);

        return response()->json(['data' => DB::table('companies')->where('id', $company->id)->first()]);
    });

    Route::post('/company/logo', function (Request $request) use ($ownCompany) {
        $company = $ownCompany($request);
        $input = $request->validate([
            'logo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:2048'],
        ]);
        $file = $input['logo'];
        $filename = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs("company-logos/{$company->id}", $filename, 'public');
        abort_if($path === false, 500, 'Gagal menyimpan logo');
        DB::table('companies')->where('id', $company->id)->update(['logo' => $filename, 'updated_at' => now()]);

        return response()->json(['data' => DB::table('companies')->where('id', $company->id)->first()]);
    });

    Route::delete('/job-posts/{id}', function (Request $request, string $id) use ($ownCompany) {
        $company = $ownCompany($request);
        $job = DB::table('job_posts')->where('id', $id)->where('company_id', $company->id)
            ->where('posted_by', $request->user()->id)->first();
        abort_if($job === null, 404);
        abort_if($job->status === 'active', 409, 'Ubah lowongan menjadi inactive dahulu');
        abort_if(DB::table('applications')->where('job_posts_id', $id)->exists(), 409, 'Lowongan memiliki lamaran');
        DB::table('job_posts')->where('id', $id)->delete();

        return response()->noContent();
    });

    Route::get('/applications/{id}/cv', function (Request $request, string $id) use ($ownCompany) {
        $company = $ownCompany($request);
        $cv = DB::table('applications')->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->where('applications.id', $id)->where('job_posts.company_id', $company->id)
            ->where('job_posts.posted_by', $request->user()->id)
            ->select('applications.cv_file', 'jobseeker_profiles.cv_filename')->first();
        abort_if($cv === null || empty($cv->cv_file) || ! Storage::disk('local')->exists($cv->cv_file), 404);

        return Storage::disk('local')->download($cv->cv_file, basename($cv->cv_filename ?: $cv->cv_file));
    });
});
