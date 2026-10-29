<?php

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Route::middleware(['web', 'auth'])->group(function (): void {
    $jobseeker = static function (Request $request): string {
        abort_unless($request->user()?->role === 'jobseeker', 403);

        return (string) $request->user()->id;
    };

    Route::get('/jobseeker/profile', function (Request $request) use ($jobseeker) {
        $userId = $jobseeker($request);
        $profile = DB::table('jobseeker_profiles')->where('user_id', $userId)->first();
        abort_if($profile === null, 404, 'Profil belum dibuat');

        return response()->json(['data' => $profile]);
    });

    Route::post('/jobseeker/profile', function (Request $request) use ($jobseeker) {
        $userId = $jobseeker($request);
        $input = $request->validate([
            'user_id' => ['missing'],
            'cv_file' => ['missing'],
            'profile_photo' => ['missing'],
            'full_name' => ['required', 'string', 'max:255'],
            'domicile' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', 'string', 'max:255'],
            'last_education' => ['required', 'string', 'max:255'],
            'portfolio_link' => ['nullable', 'url', 'max:255'],
        ]);

        $profile = DB::table('jobseeker_profiles')->where('user_id', $userId)->first();
        $values = [
            'full_name' => $input['full_name'],
            'domicile' => $input['domicile'],
            'birth_date' => $input['birth_date'],
            'gender' => $input['gender'],
            'last_education' => $input['last_education'],
            'portfolio_link' => $input['portfolio_link'] ?? null,
            'updated_at' => now(),
        ];

        if ($profile === null) {
            $profileId = (string) Str::uuid();
            DB::table('jobseeker_profiles')->insert($values + [
                'id' => $profileId, 'user_id' => $userId, 'created_at' => now(),
            ]);
        } else {
            $profileId = $profile->id;
            DB::table('jobseeker_profiles')->where('id', $profileId)->update($values);
        }

        return response()->json([
            'data' => DB::table('jobseeker_profiles')->where('id', $profileId)->first(),
        ], $profile === null ? 201 : 200);
    });

    Route::post('/jobseeker/profile/photo', function (Request $request) use ($jobseeker) {
        $userId = $jobseeker($request);
        $profile = DB::table('jobseeker_profiles')->where('user_id', $userId)->first();
        abort_if($profile === null, 404, 'Buat profil terlebih dahulu');
        $input = $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:2048'],
        ]);
        $file = $input['photo'];
        $filename = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs("profile-photos/{$userId}", $filename, 'local');
        abort_if($path === false, 500, 'Gagal menyimpan foto');

        DB::table('jobseeker_profiles')->where('id', $profile->id)->update([
            'profile_photo' => $filename, 'updated_at' => now(),
        ]);

        return response()->json(['filename' => $filename]);
    });

    Route::get('/jobseeker/profile/photo', function (Request $request) use ($jobseeker) {
        $userId = $jobseeker($request);
        $filename = DB::table('jobseeker_profiles')->where('user_id', $userId)->value('profile_photo');
        abort_if(empty($filename), 404);
        $path = "profile-photos/{$userId}/{$filename}";
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    });

    Route::post('/jobseeker/profile/cv', function (Request $request) use ($jobseeker) {
        $userId = $jobseeker($request);
        $profile = DB::table('jobseeker_profiles')->where('user_id', $userId)->first();
        abort_if($profile === null, 404, 'Buat profil terlebih dahulu');
        $input = $request->validate([
            'cv' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $file = $input['cv'];
        $filename = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs("cvs/{$userId}", $filename, 'local');
        abort_if($path === false, 500, 'Gagal menyimpan CV');

        DB::table('jobseeker_profiles')->where('id', $profile->id)->update([
            'cv_file' => $path,
            'cv_filename' => basename($file->getClientOriginalName()),
            'cv_uploaded_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['filename' => $file->getClientOriginalName(), 'uploaded' => true]);
    });

    Route::get('/jobseeker/profile/cv', function (Request $request) use ($jobseeker) {
        $userId = $jobseeker($request);
        $profile = DB::table('jobseeker_profiles')->where('user_id', $userId)->first();
        $path = $profile?->cv_file;
        abort_if(! $path || ! preg_match('/\Acvs\/'.preg_quote($userId, '/').'\/[0-9a-f-]{36}\.(?:pdf|jpe?g|png)\z/i', $path) || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $profile->cv_filename ?: 'CV');
    });

    Route::post('/job-posts/{id}/apply', function (Request $request, string $id) use ($jobseeker) {
        $userId = $jobseeker($request);
        $job = DB::table('job_posts')->where('id', $id)->first();
        abort_if($job === null, 404, 'Lowongan tidak ditemukan');
        abort_if($job->status !== 'active' || ($job->expires_at !== null && Carbon::parse($job->expires_at)->lessThanOrEqualTo(now())), 409, 'Lowongan tidak aktif');
        abort_if(! empty($job->apply_url), 409, 'Gunakan tombol Apply Sekarang');
        $profile = DB::table('jobseeker_profiles')->where('user_id', $userId)->first();
        abort_if($profile === null, 422, 'Profil wajib dilengkapi');
        abort_if(empty($profile->full_name) || empty($profile->domicile) || empty($profile->birth_date) || empty($profile->gender) || empty($profile->last_education), 422, 'Data diri belum lengkap');
        abort_unless(DB::table('skills')->where('profile_id', $profile->id)->exists(), 422, 'Skills wajib diisi');
        abort_if(empty($profile->cv_file) || ! Storage::disk('local')->exists($profile->cv_file), 422, 'Unggah CV terlebih dahulu');
        abort_if(DB::table('applications')->where('job_posts_id', $id)->where('profile_id', $profile->id)->exists(), 409, 'Sudah pernah melamar');

        $appId = (string) Str::uuid();
        try {
            DB::table('applications')->insert([
                'id' => $appId, 'job_posts_id' => $id, 'profile_id' => $profile->id,
                'applied_at' => now(), 'status' => 'submitted', 'cv_file' => $profile->cv_file,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (QueryException $e) {
            if (in_array($e->getCode(), ['23000', '23505'], true)) {
                abort(409, 'Sudah pernah melamar');
            }
            throw $e;
        }

        return response()->json(['id' => $appId, 'status' => 'submitted'], 201);
    });

    Route::get('/jobseeker/my-applications', function (Request $request) use ($jobseeker) {
        $userId = $jobseeker($request);
        $profileId = DB::table('jobseeker_profiles')->where('user_id', $userId)->value('id');
        abort_if($profileId === null, 404, 'Profil belum dibuat');
        $applications = DB::table('applications')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->where('applications.profile_id', $profileId)
            ->select('applications.id as application_id', 'applications.status', 'applications.applied_at', 'job_posts.id as job_id', 'job_posts.title', 'companies.name as company_name')
            ->orderByDesc('applications.applied_at')->get();

        return response()->json(['total' => $applications->count(), 'data' => $applications]);
    });

    Route::get('/jobseeker/my-applications/{userId}', function (Request $request, string $userId) use ($jobseeker) {
        abort_unless($userId === $jobseeker($request), 403);

        return redirect('/api/jobseeker/my-applications');
    });
});

Route::get('/employer/applicants/{companyId}', function () {
    abort(410, 'Gunakan /api/employer/applications setelah login');
});
