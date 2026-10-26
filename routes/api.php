<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/*
|--------------------------------------------------------------------------
| API Routes - StartWork Platform
| Berdasarkan PRD.md, Database.md, dan Architecture.md
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. HEALTH CHECK / DATABASE
// ==========================================
Route::get('/', function () {
    try {
        DB::select('SELECT 1');

        return response()->json([
            'status' => 'success',
            'message' => 'StartWork API is running',
            'database' => 'connected',
        ]);
    } catch (Throwable $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'disconnected',
            'message' => $e->getMessage(),
        ], 503);
    }
});

Route::get('/me', function (Request $request) {
    $user = $request->user();

    return response()->json(['data' => [
        'id' => $user->id,
        'full_name' => $user->full_name,
        'email' => $user->email,
        'role' => $user->role,
        'email_verified_at' => $user->email_verified_at,
    ]]);
})->middleware(['web', 'auth']);

// ==========================================
// 2. LOWONGAN KERJA (JOB POSTS) - PRD 3.1, 3.2, 4.1
// ==========================================

Route::get('/job-posts', function (Request $request) {
    $query = DB::table('job_posts')
        ->leftJoin('companies', 'job_posts.company_id', '=', 'companies.id')
        ->where('job_posts.status', 'active')
        ->where(function ($q) {
            $q->whereNull('job_posts.expires_at')->orWhere('job_posts.expires_at', '>', now());
        })
        ->select(
            'companies.name as company_name',
            'job_posts.id',
            'job_posts.title',
            'job_posts.category',
            'job_posts.location',
            'job_posts.job_type',
            'job_posts.work_type',
            'job_posts.salary_min',
            'job_posts.salary_max',
            'job_posts.description',
            'job_posts.responsibilities',
            'job_posts.requirements',
            'job_posts.skills',
            'job_posts.benefits',
            'job_posts.apply_url'
        );

    // Filter Kategori
    if ($request->filled('category')) {
        $query->where('job_posts.category', $request->query('category'));
    }

    // Filter Lokasi
    if ($request->filled('location')) {
        $query->where('job_posts.location', 'like', '%'.$request->query('location').'%');
    }

    // Filter Tipe Pekerjaan (Full-time, Part-time, Contract)
    if ($request->filled('job_type')) {
        $query->where('job_posts.job_type', $request->query('job_type'));
    }

    // Filter Sistem/Metode Kerja (Remote, On-site, Hybrid)
    if ($request->filled('work_type')) {
        $query->where('job_posts.work_type', $request->query('work_type'));
    }

    // Pencarian kata kunci judul atau deskripsi
    if ($request->filled('q')) {
        $searchTerm = '%'.$request->query('q').'%';
        $query->where(function ($sub) use ($searchTerm) {
            $sub->where('job_posts.title', 'like', $searchTerm)
                ->orWhere('job_posts.description', 'like', $searchTerm);
        });
    }

    // Sorting (Latest, Oldest, A-Z, Z-A)
    $sort = $request->query('sort', 'latest');
    match ($sort) {
        'oldest' => $query->orderBy('job_posts.created_at', 'asc'),
        'a-z' => $query->orderBy('job_posts.title', 'asc'),
        'z-a' => $query->orderBy('job_posts.title', 'desc'),
        default => $query->orderBy('job_posts.created_at', 'desc'),
    };

    $limit = min((int) $request->query('limit', 15), 50);
    $data = $query->limit($limit)->get();

    return response()->json([
        'total' => $data->count(),
        'data' => $data,
    ]);
});

// Form HTML Pengujian Post Job
Route::get('/job-posts/form', function () {
    abort_unless(app()->environment(['local', 'testing']), 404);
    abort_unless(request()->user()?->role === 'employer', 403);

    $csrf = e(csrf_token() ?? '');
    $employerEmail = e(request()->user()->email);

    return response(<<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StartWork - Form Input Lowongan Kerja</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
            --border: #334155;
            --input-bg: #0f172a;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body {
            background-color: var(--bg);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        .container {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 1rem;
            max-width: 640px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .header {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1rem;
        }
        .header h1 { font-size: 1.5rem; font-weight: 700; color: #fff; }
        .header p { color: var(--text-sub); font-size: 0.875rem; margin-top: 0.25rem; }
        .badge {
            display: inline-block;
            background: rgba(79, 70, 229, 0.2);
            color: #818cf8;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.5rem;
            border-radius: 0.375rem;
            margin-top: 0.5rem;
        }
        .form-group { margin-bottom: 1.25rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.35rem; color: var(--text-sub); }
        input, textarea {
            width: 100%;
            padding: 0.75rem;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            color: #fff;
            font-size: 0.875rem;
            outline: none;
        }
        input:focus, textarea:focus { border-color: var(--primary); }
        textarea { resize: vertical; min-height: 80px; }
        button {
            width: 100%;
            padding: 0.85rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { background: var(--primary-hover); }
        .nav-links { margin-top: 1.5rem; text-align: center; font-size: 0.875rem; }
        .nav-links a { color: #818cf8; text-decoration: none; margin: 0 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tambah Lowongan Kerja (Test Form)</h1>
            <p>Berdasarkan PRD StartWork: Lowongan Kerja Employer/Admin</p>
            <span class="badge">Employer: {$employerEmail}</span>
        </div>

        <form method="POST" action="/api/employer/job-posts">
            <input type="hidden" name="_token" value="{$csrf}">
            <div class="form-group">
                <label for="title">Judul Lowongan *</label>
                <input id="title" name="title" placeholder="Contoh: Senior Backend Engineer" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="location">Lokasi *</label>
                    <input id="location" name="location" placeholder="Jakarta / Remote" required>
                </div>
                <div class="form-group">
                    <label for="category">Kategori</label>
                    <input id="category" name="category" placeholder="Teknologi, Pemasaran, Desain">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="job_type">Tipe Pekerjaan *</label>
                    <input id="job_type" name="job_type" placeholder="Full-time, Part-time, Contract" required>
                </div>
                <div class="form-group">
                    <label for="work_type">Sistem Kerja *</label>
                    <input id="work_type" name="work_type" placeholder="Remote, On-site, Hybrid" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="salary_min">Gaji Minimum (Rp)</label>
                    <input id="salary_min" name="salary_min" type="number" placeholder="8000000">
                </div>
                <div class="form-group">
                    <label for="salary_max">Gaji Maksimum (Rp)</label>
                    <input id="salary_max" name="salary_max" type="number" placeholder="15000000">
                </div>
            </div>
            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" placeholder="Deskripsi pekerjaan..."></textarea>
            </div>
            <button type="submit">Simpan Lowongan Kerja</button>
        </form>

        <div class="nav-links">
            <a href="/api/job-posts">Lihat /api/job-posts</a> &bull;
            <a href="/api/companies">Lihat /api/companies</a> &bull;
            <a href="/api/articles">Lihat /api/articles</a>
        </div>
    </div>
</body>
</html>
HTML);
})->middleware(['web', 'auth']);

// Detail Job Post
Route::get('/job-posts/{id}', function (string $id) {
    $job = DB::table('job_posts')
        ->leftJoin('companies', 'job_posts.company_id', '=', 'companies.id')
        ->where('job_posts.id', $id)
        ->where('job_posts.status', 'active')
        ->where(function ($q) {
            $q->whereNull('job_posts.expires_at')->orWhere('job_posts.expires_at', '>', now());
        })
        ->select(
            'job_posts.id',
            'job_posts.company_id',
            'job_posts.title',
            'job_posts.category',
            'job_posts.location',
            'job_posts.job_type',
            'job_posts.work_type',
            'job_posts.salary_min',
            'job_posts.salary_max',
            'job_posts.description',
            'job_posts.responsibilities',
            'job_posts.requirements',
            'job_posts.skills',
            'job_posts.benefits',
            'job_posts.apply_url',
            'job_posts.status',
            'job_posts.expires_at',
            'job_posts.created_at',
            'companies.name as company_name',
            'companies.location as company_location',
            'companies.logo as company_logo',
            'companies.website as company_website'
        )
        ->first();

    abort_if($job === null, 404, 'Lowongan tidak ditemukan');

    return response()->json(['data' => $job]);
});

Route::get('/job-posts/{id}/apply-now', function (string $id) {
    $job = DB::table('job_posts')->where('id', $id)->first();
    abort_if($job === null, 404, 'Lowongan tidak ditemukan');
    abort_if($job->status !== 'active' || empty($job->apply_url) || ($job->expires_at !== null && Carbon::parse($job->expires_at)->lessThanOrEqualTo(now())), 404, 'Link pendaftaran tidak tersedia');

    $scheme = strtolower((string) parse_url($job->apply_url, PHP_URL_SCHEME));
    abort_unless(in_array($scheme, ['http', 'https', 'mailto'], true), 404);

    return redirect()->away($job->apply_url);
});

// Tambah Job Post (PRD 3.1 & 3.7)
Route::post('/job-posts', function (Request $request) {
    abort(410, 'Gunakan endpoint POST /api/employer/job-posts atau POST /api/admin/job-posts');
});

// Update Job Post
Route::put('/job-posts/{id}', function (Request $request, string $id) {
    abort(410, 'Gunakan endpoint PUT /api/employer/job-posts/{id} atau PUT /api/admin/job-posts/{id}');
});

Route::delete('/job-posts/{id}', function () {
    abort(410, 'Gunakan endpoint DELETE khusus role');
});

// ==========================================
// 3. PERUSAHAAN (COMPANIES) - PRD 4.1, 4.2
// ==========================================

Route::get('/companies', function (Request $request) {
    $query = DB::table('companies')
        ->select('id', 'name', 'location', 'logo', 'website', 'contact_url', 'description');

    if ($request->filled('q')) {
        $term = '%'.$request->query('q').'%';
        $query->where(function ($sub) use ($term) {
            $sub->where('name', 'like', $term)
                ->orWhere('location', 'like', $term);
        });
    }

    return response()->json([
        'total' => $query->count(),
        'data' => $query->get(),
    ]);
});

Route::get('/companies/{id}', function (string $id) {
    $company = DB::table('companies')->where('id', $id)
        ->select('id', 'name', 'location', 'logo', 'website', 'contact_url', 'description')->first();
    abort_if(! $company, 404, 'Perusahaan tidak ditemukan');

    // Sertakan Open Positions perusahaan tersebut (PRD 4.1)
    $openPositions = DB::table('job_posts')
        ->where('company_id', $id)
        ->where('status', 'active')
        ->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })
        ->select('id', 'company_id', 'title', 'category', 'location', 'job_type', 'work_type',
            'salary_min', 'salary_max', 'description', 'apply_url', 'expires_at')
        ->get();

    return response()->json([
        'data' => $company,
        'open_positions' => $openPositions,
    ]);
});

Route::get('/companies/{id}/logo', function (string $id) {
    $company = DB::table('companies')->where('id', $id)->first();
    abort_if($company === null || empty($company->logo), 404, 'Logo perusahaan tidak ditemukan');
    abort_if(basename($company->logo) !== $company->logo, 404);

    $path = "company-logos/{$id}/{$company->logo}";
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
});

Route::post('/companies', function () {
    abort(410, 'Gunakan endpoint POST /api/employer/company atau /api/admin/companies');
});

// ==========================================
// 4. LAMARAN KERJA (APPLICATIONS) - PRD 3.6, 7.6
// ==========================================

require __DIR__.'/secure_jobseeker.php';

// Detail profil jobseeker: skills wajib, empat bagian lain boleh kosong.
Route::middleware(['web', 'auth'])->group(function () {
    $tables = [
        'work_experiences' => [
            'job_type', 'job_title', 'company', 'start_date',
            'end_date', 'key_responsibilities',
        ],
        'organization_experiences' => [
            'organization_name', 'role', 'start_date', 'end_date', 'description',
        ],
        'education_histories' => [
            'level', 'institution', 'major', 'start_date', 'end_date',
        ],
        'certificates' => ['name', 'file_url'],
        'skills' => ['name'],
    ];

    $ownProfile = static function (Request $request): object {
        abort_unless($request->user()?->role === 'jobseeker', 403);

        $profile = DB::table('jobseeker_profiles')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->first();

        abort_if(! $profile, 404, 'Buat profil jobseeker terlebih dahulu');

        return $profile;
    };

    Route::get('/jobseeker/profile/details', function (Request $request) use ($tables, $ownProfile) {
        $profile = $ownProfile($request);
        $data = [];

        foreach ($tables as $table => $columns) {
            $data[$table] = DB::table($table)
                ->where('profile_id', $profile->id)
                ->orderBy('created_at')
                ->get();
        }

        return response()->json([
            'profile_id' => $profile->id,
            'complete' => $data['skills']->isNotEmpty(),
            'data' => $data,
        ]);
    });

    Route::match(['post', 'put'], '/jobseeker/profile/details', function (Request $request) use ($tables, $ownProfile) {
        $profile = $ownProfile($request);

        $input = $request->validate([
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => ['required', 'string', 'max:255'],

            'work_experiences' => ['nullable', 'array'],
            'work_experiences.*' => ['array'],
            'work_experiences.*.job_type' => ['nullable', 'string', 'max:255'],
            'work_experiences.*.job_title' => ['required', 'string', 'max:255'],
            'work_experiences.*.company' => ['required', 'string', 'max:255'],
            'work_experiences.*.start_date' => ['required', 'date'],
            'work_experiences.*.end_date' => ['nullable', 'date'],
            'work_experiences.*.key_responsibilities' => ['nullable', 'string'],

            'organization_experiences' => ['nullable', 'array'],
            'organization_experiences.*' => ['array'],
            'organization_experiences.*.organization_name' => ['nullable', 'string', 'max:255'],
            'organization_experiences.*.role' => ['nullable', 'string', 'max:255'],
            'organization_experiences.*.start_date' => ['nullable', 'date'],
            'organization_experiences.*.end_date' => ['nullable', 'date'],
            'organization_experiences.*.description' => ['nullable', 'string'],

            'education_histories' => ['nullable', 'array'],
            'education_histories.*' => ['array'],
            'education_histories.*.level' => ['nullable', 'string', 'max:255'],
            'education_histories.*.institution' => ['nullable', 'string', 'max:255'],
            'education_histories.*.major' => ['nullable', 'string', 'max:255'],
            'education_histories.*.start_date' => ['nullable', 'date'],
            'education_histories.*.end_date' => ['nullable', 'date'],

            'certificates' => ['nullable', 'array'],
            'certificates.*' => ['array'],
            'certificates.*.name' => ['nullable', 'string', 'max:255'],
            'certificates.*.file_url' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($profile, $input, $tables): void {
            foreach ($tables as $table => $columns) {
                DB::table($table)->where('profile_id', $profile->id)->delete();

                foreach ($input[$table] ?? [] as $entry) {
                    $row = [
                        'id' => (string) Str::uuid(),
                        'profile_id' => $profile->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if ($table === 'skills') {
                        $row['name'] = $entry;
                    } else {
                        foreach ($columns as $column) {
                            $row[$column] = $entry[$column] ?? null;
                        }
                    }

                    DB::table($table)->insert($row);
                }
            }
        });

        return response()->json([
            'message' => 'Detail profil tersimpan',
            'profile_id' => $profile->id,
        ]);
    });

    Route::delete('/jobseeker/profile/details/{section}', function (Request $request, string $section) use ($ownProfile) {
        abort_unless(in_array($section, [
            'work_experiences',
            'organization_experiences',
            'education_histories',
            'certificates',
        ], true), 404);

        $profile = $ownProfile($request);
        $deleted = DB::table($section)->where('profile_id', $profile->id)->delete();

        return response()->json(['deleted' => $deleted]);
    });
});

// ==========================================
// 5. ARTIKEL / BLOG (PRD 3.3, 4.1, 4.3)
// ==========================================

// Daftar Artikel Publik (Hanya yang berstatus 'published' - PRD 4.3)
Route::get('/articles', function () {
    $articles = DB::table('articles')
        ->join('users', 'articles.author_id', '=', 'users.id')
        ->where('articles.status', 'published')
        ->select(
            'articles.id',
            'articles.title',
            'articles.cover_image',
            'articles.content',
            'articles.published_at',
            'users.full_name as author_name'
        )
        ->orderBy('articles.published_at', 'desc')
        ->get();

    return response()->json([
        'total' => $articles->count(),
        'data' => $articles,
    ]);
});

// Detail Artikel
Route::get('/articles/{id}', function (string $id) {
    $article = DB::table('articles')
        ->join('users', 'articles.author_id', '=', 'users.id')
        ->where('articles.id', $id)
        ->where('articles.status', 'published')
        ->select('articles.*', 'users.full_name as author_name')
        ->first();

    abort_if(! $article, 404, 'Artikel tidak ditemukan');

    return response()->json(['data' => $article]);
});

require __DIR__.'/secure_articles.php';

// ==========================================
// 6. PACKAGES & SUBSCRIPTIONS (PRD 3.7 & Database.md)
// ==========================================

// Katalog Package (Basic: Rp 99.000, Standard: Rp 149.000, Premium: Rp 239.000 - PRD 3.7)
Route::get('/packages', function () {
    $packages = DB::table('packages')->where('is_active', true)->get();

    return response()->json([
        'total' => $packages->count(),
        'data' => $packages,
    ]);
});

Route::post('/admin/companies/{companyId}/grant-trial', function () {
    abort(501, 'Subscription dan payment ditunda sesuai keputusan PIC');
});

$applyUrlRule = static function (string $attribute, mixed $value, Closure $fail): void {
    if (! is_string($value)) {
        $fail('Link pendaftaran tidak valid.');

        return;
    }

    if (str_starts_with(strtolower($value), 'mailto:')) {
        if (filter_var(substr($value, 7), FILTER_VALIDATE_EMAIL)) {
            return;
        }
    } elseif (
        filter_var($value, FILTER_VALIDATE_URL)
        && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)
    ) {
        return;
    }

    $fail('Link pendaftaran harus berupa URL HTTP(S) atau mailto:alamat@email.');
};

Route::middleware(['web', 'auth'])->prefix('admin')->group(function () use ($applyUrlRule) {
    Route::post('/companies', function (Request $request) {
        abort_unless($request->user()->role === 'admin', 403);

        $input = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'logo' => ['missing'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        abort_if(
            DB::table('companies')->where('name', $input['name'])->where('owner_user_id', $request->user()->id)->exists(),
            409,
            'Perusahaan sudah terdaftar; pilih perusahaan yang ada'
        );

        $companyId = (string) Str::uuid();
        DB::table('companies')->insert([
            'id' => $companyId,
            'owner_user_id' => $request->user()->id,
            'created_by' => $request->user()->id,
            'name' => $input['name'],
            'location' => $input['location'],
            'logo' => null,
            'website' => $input['website'] ?? null,
            'contact_url' => $input['contact_url'] ?? null,
            'description' => $input['description'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Perusahaan berhasil dibuat oleh admin',
            'data' => DB::table('companies')->where('id', $companyId)->first(),
        ], 201);
    });

    Route::post('/companies/{companyId}/logo', function (Request $request, string $companyId) {
        abort_unless($request->user()->role === 'admin', 403);

        $company = DB::table('companies')
            ->where('id', $companyId)
            ->where('owner_user_id', $request->user()->id)
            ->first();
        abort_if($company === null, 404, 'Perusahaan milik admin tidak ditemukan');

        $input = $request->validate([
            'logo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:2048'],
        ]);

        $file = $input['logo'];
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'logo';
        $filename = $baseName.'.'.strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs("company-logos/{$companyId}", $filename, 'public');
        abort_if($path === false, 500, 'Gagal menyimpan logo perusahaan');

        DB::table('companies')->where('id', $companyId)->update([
            'logo' => $filename,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Logo perusahaan tersimpan',
            'data' => DB::table('companies')->where('id', $companyId)->first(),
        ]);
    });

    Route::post('/companies/{companyId}/assign-employer', function () {
        abort(410, 'Company admin dan employer memiliki alur terpisah');
    });

    Route::post('/job-posts', function (Request $request) use ($applyUrlRule) {
        abort_unless($request->user()->role === 'admin', 403);

        $input = $request->validate([
            'company_id' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'job_type' => ['required', 'string', 'max:255'],
            'work_type' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'skills' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date'],
            'apply_url' => ['nullable', 'string', 'max:2048', $applyUrlRule],
        ]);

        abort_unless(DB::table('companies')->where('id', $input['company_id'])
            ->where('owner_user_id', $request->user()->id)->exists(), 404);

        $jobId = (string) Str::uuid();
        DB::table('job_posts')->insert([
            'id' => $jobId,
            'company_id' => $input['company_id'],
            'posted_by' => $request->user()->id,
            'subscription_id' => null,
            'title' => $input['title'],
            'category' => $input['category'] ?? 'Umum',
            'location' => $input['location'],
            'job_type' => $input['job_type'],
            'work_type' => $input['work_type'],
            'salary_min' => $input['salary_min'] ?? null,
            'salary_max' => $input['salary_max'] ?? null,
            'description' => $input['description'] ?? null,
            'responsibilities' => $input['responsibilities'] ?? null,
            'requirements' => $input['requirements'] ?? null,
            'skills' => $input['skills'] ?? null,
            'benefits' => $input['benefits'] ?? null,
            'apply_url' => $input['apply_url'] ?? null,
            'status' => 'active',
            'expires_at' => isset($input['expires_at'])
                ? Carbon::parse($input['expires_at'])->utc()->format('Y-m-d H:i:s')
                : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Lowongan berhasil dibuat oleh admin',
            'data' => DB::table('job_posts')->where('id', $jobId)->first(),
        ], 201);
    });

    Route::put('/job-posts/{id}', function (Request $request, string $id) use ($applyUrlRule) {
        abort_unless($request->user()->role === 'admin', 403);

        $job = DB::table('job_posts')
            ->where('id', $id)
            ->where('posted_by', $request->user()->id)
            ->whereIn('company_id', DB::table('companies')->where('owner_user_id', $request->user()->id)->select('id'))
            ->first();
        abort_if($job === null, 404, 'Lowongan admin tidak ditemukan');

        $input = $request->validate([
            'id' => ['missing'],
            'company_id' => ['missing'],
            'posted_by' => ['missing'],
            'subscription_id' => ['missing'],
            'created_at' => ['missing'],
            'updated_at' => ['missing'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'job_type' => ['sometimes', 'required', 'string', 'max:255'],
            'work_type' => ['sometimes', 'required', 'string', 'max:255'],
            'salary_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'salary_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
            'responsibilities' => ['sometimes', 'nullable', 'string'],
            'requirements' => ['sometimes', 'nullable', 'string'],
            'skills' => ['sometimes', 'nullable', 'string'],
            'benefits' => ['sometimes', 'nullable', 'string'],
            'status' => ['missing'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
            'apply_url' => ['sometimes', 'nullable', 'string', 'max:2048', $applyUrlRule],
        ]);

        if (isset($input['expires_at'])) {
            $input['expires_at'] = Carbon::parse($input['expires_at'])->utc()->format('Y-m-d H:i:s');
        }
        $input['updated_at'] = now();
        DB::table('job_posts')->where('id', $id)->update($input);

        return response()->json([
            'message' => 'Lowongan admin diperbarui',
            'data' => DB::table('job_posts')->where('id', $id)->first(),
        ]);
    });

    Route::get('/applicants', function (Request $request) {
        abort_unless($request->user()->role === 'admin', 403);

        $filters = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'uuid'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = DB::table('applications')
            ->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->select(
                'applications.id as application_id',
                'applications.status',
                'applications.applied_at',
                'jobseeker_profiles.user_id',
                'jobseeker_profiles.full_name as applicant_name',
                'companies.id as company_id',
                'companies.name as company_name',
                'job_posts.id as job_id',
                'job_posts.title as job_title'
            );

        if (! empty($filters['name'])) {
            $query->where('jobseeker_profiles.full_name', 'like', '%'.$filters['name'].'%');
        }
        if (! empty($filters['company_id'])) {
            $query->where('companies.id', $filters['company_id']);
        }

        $total = $query->count();
        $limit = $filters['limit'] ?? 15;
        $page = $filters['page'] ?? 1;
        $applicants = $query->orderByDesc('applications.applied_at')
            ->orderBy('applications.id')
            ->skip(($page - 1) * $limit)->take($limit)->get();

        return response()->json([
            'total' => $total,
            'data' => $applicants,
        ]);
    });
});

// Registrasi employer: akun dibuat pada tabel users yang sudah digunakan aplikasi.
Route::post('/employer/register', function (Request $request) {
    $input = $request->validate([
        'full_name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'confirmed', Password::min(8)],
    ]);

    $user = User::create([
        'full_name' => $input['full_name'],
        'phone' => $input['phone'],
        'email' => $input['email'],
        'password_hash' => Hash::make($input['password']),
        'role' => 'employer',
    ]);

    event(new Registered($user));
    Auth::login($user);
    $request->session()->regenerate();

    return response()->json([
        'message' => 'Akun employer berhasil dibuat',
        'data' => [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => $user->role,
        ],
    ], 201);
})->middleware(['web', 'guest']);

Route::middleware(['web', 'auth'])->prefix('employer')->group(function () {
    Route::get('/company', function (Request $request) {
        abort_unless($request->user()->role === 'employer', 403);

        $company = DB::table('companies')
            ->where('owner_user_id', $request->user()->id)
            ->first();
        abort_if($company === null, 404, 'Employer belum terhubung ke perusahaan');

        return response()->json(['data' => $company]);
    });

    Route::get('/job-posts', function (Request $request) {
        abort_unless($request->user()->role === 'employer', 403);

        $company = DB::table('companies')
            ->where('owner_user_id', $request->user()->id)
            ->first();
        abort_if($company === null, 404, 'Employer belum terhubung ke perusahaan');

        $jobs = DB::table('job_posts')
            ->where('company_id', $company->id)
            ->where('posted_by', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'company' => $company,
            'total' => $jobs->count(),
            'data' => $jobs,
        ]);
    });

    Route::post('/company', function (Request $request) {
        abort_unless($request->user()->role === 'employer', 403);

        $input = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'logo' => ['missing'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($request, $input) {
            DB::table('users')->where('id', $request->user()->id)->lockForUpdate()->first();
            abort_if(DB::table('companies')->where('owner_user_id', $request->user()->id)->exists(),
                409, 'Employer ini sudah memiliki perusahaan');

            $id = (string) Str::uuid();
            DB::table('companies')->insert([
                'id' => $id,
                'owner_user_id' => $request->user()->id,
                'created_by' => $request->user()->id,
                'name' => $input['name'],
                'location' => $input['location'],
                'logo' => null,
                'website' => $input['website'] ?? null,
                'contact_url' => $input['contact_url'] ?? null,
                'description' => $input['description'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Perusahaan berhasil dibuat',
                'data' => DB::table('companies')->where('id', $id)->first(),
            ], 201);
        });
    });

    Route::get('/applications', function (Request $request) {
        abort_unless($request->user()->role === 'employer', 403);

        $filters = $request->validate([
            'job_id' => ['sometimes', 'uuid'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $query = DB::table('applications')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->where('companies.owner_user_id', $request->user()->id)
            ->where('job_posts.posted_by', $request->user()->id)
            ->select(
                'applications.id as application_id',
                'applications.status',
                'applications.applied_at',
                'job_posts.id as job_id',
                'job_posts.title as job_title',
                'jobseeker_profiles.full_name as applicant_name'
            );

        if ($request->filled('job_id')) {
            $query->where('job_posts.id', $request->query('job_id'));
        }

        $total = $query->count();
        $limit = $filters['limit'] ?? 15;
        $page = $filters['page'] ?? 1;
        $applications = $query->orderByDesc('applications.applied_at')
            ->orderByDesc('applications.id')
            ->skip(($page - 1) * $limit)->take($limit)->get();

        return response()->json([
            'total' => $total,
            'data' => $applications,
        ]);
    });

    Route::get('/applications/{id}', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'employer', 403);

        $application = DB::table('applications')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->join('jobseeker_profiles', 'applications.profile_id', '=', 'jobseeker_profiles.id')
            ->where('applications.id', $id)
            ->where('companies.owner_user_id', $request->user()->id)
            ->where('job_posts.posted_by', $request->user()->id)
            ->select(
                'applications.id as application_id',
                'applications.status',
                'applications.applied_at',
                DB::raw('CASE WHEN applications.cv_file IS NULL THEN 0 ELSE 1 END as has_cv'),
                'job_posts.id as job_id',
                'job_posts.title as job_title',
                'jobseeker_profiles.full_name',
                'jobseeker_profiles.domicile',
                'jobseeker_profiles.birth_date',
                'jobseeker_profiles.gender',
                'jobseeker_profiles.last_education',
                'jobseeker_profiles.portfolio_link'
            )
            ->first();

        abort_if(! $application, 404, 'Lamaran tidak ditemukan');

        return response()->json(['data' => $application]);
    });

    Route::put('/applications/{id}/status', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'employer', 403);

        $input = $request->validate([
            'status' => ['required', 'in:in_process,accepted,rejected'],
        ]);

        $application = DB::table('applications')
            ->join('job_posts', 'applications.job_posts_id', '=', 'job_posts.id')
            ->join('companies', 'job_posts.company_id', '=', 'companies.id')
            ->where('applications.id', $id)
            ->where('companies.owner_user_id', $request->user()->id)
            ->where('job_posts.posted_by', $request->user()->id)
            ->select('applications.id', 'applications.status')
            ->first();

        abort_if(! $application, 404, 'Lamaran tidak ditemukan');

        $allowed = match ($application->status) {
            'submitted' => ['in_process', 'accepted', 'rejected'],
            'in_process' => ['accepted', 'rejected'],
            default => [],
        };

        abort_unless(
            in_array($input['status'], $allowed, true),
            409,
            'Perubahan status lamaran tidak diizinkan'
        );

        $updated = DB::table('applications')
            ->where('id', $id)
            ->where('status', $application->status)
            ->update([
                'status' => $input['status'],
                'updated_at' => now(),
            ]);

        abort_if($updated !== 1, 409, 'Status lamaran telah berubah; muat ulang data');

        return response()->json([
            'message' => 'Status lamaran berhasil diperbarui',
            'data' => DB::table('applications')
                ->where('id', $id)
                ->select('id', 'status', 'updated_at')
                ->first(),
        ]);
    });
});

require __DIR__.'/job_status.php';
require __DIR__.'/secure_management.php';
