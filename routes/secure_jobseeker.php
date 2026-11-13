<?php

use App\JobseekerDocument;
use App\WebpImage;
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

    Route::get('/jobseeker/saved-jobs', function (Request $request) use ($jobseeker) {
        return response()->json(['data' => DB::table('saved_job_posts')
            ->where('user_id', $jobseeker($request))->pluck('job_post_id')]);
    });

    Route::post('/jobseeker/saved-jobs/{id}', function (Request $request, string $id) use ($jobseeker) {
        $userId = $jobseeker($request);
        abort_unless(DB::table('job_posts')->where('id', $id)->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists(), 404);
        DB::table('saved_job_posts')->insertOrIgnore(['user_id' => $userId, 'job_post_id' => $id, 'created_at' => now()]);

        return response()->json(['saved' => true]);
    });

    Route::delete('/jobseeker/saved-jobs/{id}', function (Request $request, string $id) use ($jobseeker) {
        DB::table('saved_job_posts')->where('user_id', $jobseeker($request))->where('job_post_id', $id)->delete();

        return response()->json(['saved' => false]);
    });

    Route::get('/jobseeker/profile', function (Request $request) use ($jobseeker) {
        $userId = $jobseeker($request);
        $profile = DB::table('jobseeker_profiles')->where('user_id', $userId)->first();
        abort_if($profile === null, 404, 'Profil belum dibuat');

        $profile->cv_size = $profile->cv_file && JobseekerDocument::disk()->exists($profile->cv_file)
            ? JobseekerDocument::disk()->size($profile->cv_file) : null;

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
            'birth_date' => ['required', 'date', 'after_or_equal:'.today()->subYearsNoOverflow(70)->toDateString(), 'before_or_equal:'.today()->subYearsNoOverflow(18)->toDateString()],
            'gender' => ['required', 'in:male,female,Laki-laki,Perempuan'],
            'last_education' => ['required', 'string', 'max:255'],
            'portfolio_link' => ['nullable', 'url', 'max:255'],
        ], [
            'birth_date.after_or_equal' => 'Usia maksimal 70 tahun.',
            'birth_date.before_or_equal' => 'Usia minimal 18 tahun.',
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
        $input = $request->validate(['photo' => ['required', ...WebpImage::rules()]], [
            'photo.required' => 'Pilih foto profil terlebih dahulu.',
            'photo.image' => 'Foto harus berupa gambar JPG, JPEG, atau PNG yang valid.',
            'photo.mimes' => 'Foto harus berupa JPG, JPEG, atau PNG.',
            'photo.extensions' => 'Foto harus berupa JPG, JPEG, atau PNG.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ]);
        $path = WebpImage::store($input['photo'], "profile-photos/{$userId}", 'photo', disk: 'local', maxDimension: 1024);
        $filename = basename($path);
        try {
            DB::table('jobseeker_profiles')->where('id', $profile->id)->update([
                'profile_photo' => $filename, 'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        if ($profile->profile_photo && preg_match('/\A[0-9a-f-]{36}\.(?:jpe?g|png|webp)\z/i', $profile->profile_photo)) {
            Storage::disk('local')->delete("profile-photos/{$userId}/{$profile->profile_photo}");
        }

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
        $input = $request->validate(['cv' => JobseekerDocument::rules()], [
            'cv.required' => 'Pilih CV terlebih dahulu.',
            'cv.mimes' => 'CV harus berupa PDF, JPG, JPEG, atau PNG.',
            'cv.extensions' => 'CV harus berupa PDF, JPG, JPEG, atau PNG.',
        ]);
        $file = $input['cv'];
        $filename = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs("cvs/{$userId}", $filename, config('filesystems.jobseeker_documents_disk'));
        abort_if($path === false, 500, 'Gagal menyimpan CV');

        try {
            DB::transaction(function () use ($profile, $path, $file): void {
                DB::table('jobseeker_profiles')->where('id', $profile->id)->update([
                    'cv_file' => $path,
                    'cv_filename' => basename($file->getClientOriginalName()),
                    'cv_uploaded_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            JobseekerDocument::disk()->delete($path);
            throw $exception;
        }

        if (JobseekerDocument::ownedPath($profile->cv_file, 'cvs', $userId) && ! DB::table('applications')->where('cv_file', $profile->cv_file)->exists()) {
            JobseekerDocument::disk()->delete($profile->cv_file);
        }

        return response()->json(['filename' => $file->getClientOriginalName(), 'uploaded' => true]);
    });

    Route::get('/jobseeker/profile/cv', function (Request $request) use ($jobseeker) {
        $userId = $jobseeker($request);
        $profile = DB::table('jobseeker_profiles')->where('user_id', $userId)->first();
        $path = $profile?->cv_file;
        abort_if(! JobseekerDocument::ownedPath($path, 'cvs', $userId) || ! JobseekerDocument::disk()->exists($path), 404);

        if ($request->boolean('preview')) {
            return JobseekerDocument::disk()->response($path, $profile->cv_filename ?: 'CV', ['X-Content-Type-Options' => 'nosniff'], 'inline');
        }

        return JobseekerDocument::disk()->download($path, $profile->cv_filename ?: 'CV');
    });

    Route::delete('/jobseeker/profile/cv', function (Request $request) use ($jobseeker) {
        $userId = $jobseeker($request);
        $profile = DB::table('jobseeker_profiles')->where('user_id', $userId)->first();
        abort_if($profile === null || ! $profile->cv_file, 404);
        DB::transaction(function () use ($profile): void {
            DB::table('jobseeker_profiles')->where('id', $profile->id)->update([
                'cv_file' => null, 'cv_filename' => null, 'cv_uploaded_at' => null, 'updated_at' => now(),
            ]);
        });
        if (JobseekerDocument::ownedPath($profile->cv_file, 'cvs', $userId) && ! DB::table('applications')->where('cv_file', $profile->cv_file)->exists()) {
            JobseekerDocument::disk()->delete($profile->cv_file);
        }

        return response()->noContent();
    });

    Route::post('/jobseeker/profile/certificates/{id?}', function (Request $request, ?string $id = null) use ($jobseeker) {
        $userId = $jobseeker($request);
        $profile = DB::table('jobseeker_profiles')->where('user_id', $userId)->first();
        abort_if($profile === null, 404, 'Buat profil terlebih dahulu');
        $existing = $id ? DB::table('certificates')->where('profile_id', $profile->id)->where('id', $id)->first() : null;
        abort_if($id && ! $existing, 404);
        $input = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'file' => $id ? ['sometimes', ...array_slice(JobseekerDocument::rules(), 1)] : JobseekerDocument::rules(),
        ]);
        $path = isset($input['file']) ? $input['file']->storeAs("certificates/{$userId}", Str::uuid().'.'.strtolower($input['file']->getClientOriginalExtension()), config('filesystems.jobseeker_documents_disk')) : null;
        abort_if(isset($input['file']) && $path === false, 500, 'Gagal menyimpan sertifikat');

        try {
            DB::transaction(function () use ($profile, $existing, $input, $path, $id): void {
                if ($existing) {
                    DB::table('certificates')->where('id', $id)->where('profile_id', $profile->id)->update([
                        'name' => $input['name'], 'file_path' => $path ?: $existing->file_path, 'updated_at' => now(),
                    ]);
                } else {
                    DB::table('certificates')->insert([
                        'id' => (string) Str::uuid(), 'profile_id' => $profile->id, 'name' => $input['name'],
                        'file_path' => $path, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            if ($path) {
                JobseekerDocument::disk()->delete($path);
            }
            throw $exception;
        }
        if ($path && JobseekerDocument::ownedPath($existing?->file_path, 'certificates', $userId)) {
            JobseekerDocument::disk()->delete($existing->file_path);
        }

        return response()->json(['saved' => true]);
    })->whereUuid('id');

    Route::get('/jobseeker/profile/certificates/{id}/file', function (Request $request, string $id) use ($jobseeker) {
        $userId = $jobseeker($request);
        $profileId = DB::table('jobseeker_profiles')->where('user_id', $userId)->value('id');
        $certificate = DB::table('certificates')->where('profile_id', $profileId)->where('id', $id)->first();
        $path = $certificate?->file_path;
        abort_if(! JobseekerDocument::ownedPath($path, 'certificates', $userId) || ! JobseekerDocument::disk()->exists($path), 404);
        $name = ($certificate->name ?: 'Sertifikat').'.'.pathinfo($path, PATHINFO_EXTENSION);

        return $request->boolean('preview')
            ? JobseekerDocument::disk()->response($path, $name, ['X-Content-Type-Options' => 'nosniff'], 'inline')
            : JobseekerDocument::disk()->download($path, $name);
    })->whereUuid('id');

    Route::delete('/jobseeker/profile/certificates/{id}', function (Request $request, string $id) use ($jobseeker) {
        $userId = $jobseeker($request);
        $profileId = DB::table('jobseeker_profiles')->where('user_id', $userId)->value('id');
        $certificate = DB::table('certificates')->where('profile_id', $profileId)->where('id', $id)->first();
        abort_if(! $certificate, 404);
        DB::transaction(fn () => DB::table('certificates')->where('id', $id)->where('profile_id', $profileId)->delete());
        if (JobseekerDocument::ownedPath($certificate->file_path, 'certificates', $userId)) {
            JobseekerDocument::disk()->delete($certificate->file_path);
        }

        return response()->noContent();
    })->whereUuid('id');

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
        abort_if(empty($profile->cv_file) || ! JobseekerDocument::disk()->exists($profile->cv_file), 422, 'Unggah CV terlebih dahulu');
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
        $filters = $request->validate([
            'job_id' => ['sometimes', 'uuid'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'between:1,50'],
        ]);
        $profileId = DB::table('jobseeker_profiles')->where('user_id', $userId)->value('id');
        abort_if($profileId === null, 404, 'Profil belum dibuat');
        $query = DB::table('applications')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->where('applications.profile_id', $profileId)
            ->select('applications.id as application_id', 'applications.status', 'applications.applied_at', 'job_posts.id as job_id', 'job_posts.title', 'companies.name as company_name')
            ->when(isset($filters['job_id']), fn ($items) => $items->where('applications.job_posts_id', $filters['job_id']));
        $total = (clone $query)->count();
        if (isset($filters['page']) || isset($filters['limit'])) {
            $limit = (int) ($filters['limit'] ?? 12);
            $query->offset(((int) ($filters['page'] ?? 1) - 1) * $limit)->limit($limit);
        }
        $applications = $query->orderByDesc('applications.applied_at')->orderByDesc('applications.id')->get();

        return response()->json(['total' => $total, 'data' => $applications]);
    });

    Route::get('/jobseeker/my-applications/{userId}', function (Request $request, string $userId) use ($jobseeker) {
        abort_unless($userId === $jobseeker($request), 403);

        return redirect('/api/jobseeker/my-applications');
    });
});

Route::get('/employer/applicants/{companyId}', function () {
    abort(410, 'Gunakan /api/employer/applications setelah login');
});
