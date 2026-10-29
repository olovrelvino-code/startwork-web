<?php

use App\JobCategory;
use App\JobPostLifecycle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::middleware(['web', 'auth'])->prefix('admin')->group(function (): void {
    Route::get('/job-posts', function (Request $request) {
        abort_unless($request->user()->role === 'admin', 403);
        JobPostLifecycle::expireDue();

        $filters = $request->validate([
            'status' => ['sometimes', 'in:active,inactive,expired'],
            'company_id' => ['sometimes', 'uuid'],
            'mine' => ['sometimes', 'boolean'],
        ]);

        $query = DB::table('job_posts')
            ->join('companies', 'companies.id', '=', 'job_posts.company_id')
            ->select('job_posts.*', 'companies.name as company_name');

        if (isset($filters['status'])) {
            $query->where('job_posts.status', $filters['status']);
        }
        if (isset($filters['company_id'])) {
            $query->where('job_posts.company_id', $filters['company_id']);
        }
        if ($request->boolean('mine')) {
            $query->where('job_posts.posted_by', $request->user()->id);
        }

        $jobs = $query->orderByDesc('job_posts.created_at')->get();

        return response()->json(['total' => $jobs->count(), 'data' => $jobs]);
    });

    Route::put('/job-posts/{id}/status', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'admin', 403);
        JobPostLifecycle::expireDue();

        $input = $request->validate([
            'status' => ['required', 'in:active,inactive'],
            'inactive_reason' => ['required_if:status,inactive', 'nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($request, $id, $input) {
            $job = DB::table('job_posts')
                ->join('companies', 'job_posts.company_id', '=', 'companies.id')
                ->join('users as posters', 'job_posts.posted_by', '=', 'posters.id')
                ->where('job_posts.id', $id)
                ->where(function ($query) use ($request): void {
                    $query->where(function ($owned) use ($request): void {
                        $owned->where('job_posts.posted_by', $request->user()->id)
                            ->where('companies.owner_user_id', $request->user()->id);
                    })->orWhere(function ($employer): void {
                        $employer->where('posters.role', 'employer')
                            ->whereColumn('companies.owner_user_id', 'job_posts.posted_by');
                    });
                })
                ->select('job_posts.*', 'posters.role as poster_role')
                ->lockForUpdate()->first();
            abort_if($job === null, 404, 'Lowongan tidak ditemukan');

            if ($input['status'] === 'active') {
                abort_unless($job->posted_by === $request->user()->id, 403, 'Aktivasi lowongan Employer dilakukan oleh Employer');
            }

            if ($input['status'] === 'active') {
                abort_if(
                    $job->expires_at === null
                        || Carbon::parse($job->expires_at)->lessThanOrEqualTo(now()),
                    422,
                    'Isi expires_at di masa depan sebelum mengaktifkan lowongan'
                );
            }

            DB::table('job_posts')->where('id', $id)->update([
                'status' => $input['status'],
                'inactive_reason' => $input['status'] === 'inactive'
                    ? $input['inactive_reason'] : null,
                'updated_at' => now(),
            ]);

            if ($input['status'] === 'inactive' && $job->status === 'active' && $job->subscription_id !== null) {
                DB::table('company_subscriptions')->where('id', $job->subscription_id)
                    ->where('quota_used', '>', 0)->decrement('quota_used');
            }

            return response()->json([
                'data' => DB::table('job_posts')->where('id', $id)->first(),
            ]);
        });
    });
});

Route::middleware(['web', 'auth'])->prefix('employer')->group(function (): void {
    Route::post('/job-posts', function (Request $request) {
        abort_unless($request->user()->role === 'employer', 403);

        $input = $request->validate([
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
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        return DB::transaction(function () use ($request, $input) {
            $company = DB::table('companies')->where('owner_user_id', $request->user()->id)
                ->lockForUpdate()->first();
            abort_if($company === null, 404, 'Perusahaan employer tidak ditemukan');

            $id = (string) Str::uuid();
            DB::table('job_posts')->insert([
                'id' => $id,
                'company_id' => $company->id,
                'posted_by' => $request->user()->id,
                'subscription_id' => null,
                'title' => $input['title'],
                'category' => JobCategory::save($input['category'] ?? 'Umum'),
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
                'status' => 'inactive',
                'inactive_reason' => null,
                'expires_at' => isset($input['expires_at'])
                    ? Carbon::parse($input['expires_at'])->utc()->format('Y-m-d H:i:s')
                    : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'data' => DB::table('job_posts')->where('id', $id)->first(),
            ], 201);
        });
    });

    Route::put('/job-posts/{id}', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'employer', 403);

        $input = $request->validate([
            'id' => ['missing'],
            'company_id' => ['missing'],
            'posted_by' => ['missing'],
            'subscription_id' => ['missing'],
            'status' => ['missing'],
            'inactive_reason' => ['missing'],
            'created_at' => ['missing'],
            'updated_at' => ['missing'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'job_type' => ['sometimes', 'required', 'string', 'max:255'],
            'work_type' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'salary_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'salary_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
            'responsibilities' => ['sometimes', 'nullable', 'string'],
            'requirements' => ['sometimes', 'nullable', 'string'],
            'skills' => ['sometimes', 'nullable', 'string'],
            'benefits' => ['sometimes', 'nullable', 'string'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $job = DB::table('job_posts')->where('id', $id)
            ->where('posted_by', $request->user()->id)
            ->whereIn('company_id', DB::table('companies')->where('owner_user_id', $request->user()->id)->select('id'))->first();
        abort_if($job === null, 404, 'Lowongan employer tidak ditemukan');

        if ($job->status === 'active' && array_key_exists('expires_at', $input)) {
            abort_if(
                $input['expires_at'] === null
                    || Carbon::parse($input['expires_at'])->lessThanOrEqualTo(now()),
                422,
                'expires_at lowongan aktif harus di masa depan'
            );
            if ($job->subscription_id !== null) {
                $subscriptionExpiry = DB::table('company_subscriptions')
                    ->where('id', $job->subscription_id)->value('expires_at');
                abort_if(
                    $subscriptionExpiry !== null
                        && Carbon::parse($input['expires_at'])->greaterThan($subscriptionExpiry),
                    422,
                    'expires_at lowongan melewati masa subscription'
                );
            }
        }

        if (isset($input['expires_at'])) {
            $input['expires_at'] = Carbon::parse($input['expires_at'])->utc()->format('Y-m-d H:i:s');
        }
        if (isset($input['category'])) {
            $input['category'] = JobCategory::save($input['category']);
        }
        $input['updated_at'] = now();
        DB::table('job_posts')->where('id', $id)->update($input);

        return response()->json([
            'data' => DB::table('job_posts')->where('id', $id)->first(),
        ]);
    });

    Route::put('/job-posts/{id}/status', function (Request $request, string $id) {
        abort_unless($request->user()->role === 'employer', 403);
        JobPostLifecycle::expireDue();

        $input = $request->validate([
            'status' => ['required', 'in:active,inactive'],
            'inactive_reason' => ['required_if:status,inactive', 'nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($request, $id, $input) {
            $company = DB::table('companies')
                ->where('owner_user_id', $request->user()->id)->lockForUpdate()->first();
            abort_if($company === null, 404, 'Perusahaan employer tidak ditemukan');

            $job = DB::table('job_posts')->where('id', $id)
                ->where('company_id', $company->id)
                ->where('posted_by', $request->user()->id)
                ->lockForUpdate()->first();
            abort_if($job === null, 404, 'Lowongan employer tidak ditemukan');

            if ($input['status'] === 'inactive') {
                DB::table('job_posts')->where('id', $id)->update([
                    'status' => 'inactive',
                    'inactive_reason' => $input['inactive_reason'],
                    'updated_at' => now(),
                ]);

                if ($job->status === 'active' && $job->subscription_id !== null) {
                    DB::table('company_subscriptions')
                        ->where('id', $job->subscription_id)
                        ->where('quota_used', '>', 0)
                        ->decrement('quota_used');
                }

                return response()->json([
                    'data' => DB::table('job_posts')->where('id', $id)->first(),
                ]);
            }

            if ($job->status === 'active') {
                return response()->json(['data' => $job]);
            }

            abort_if(
                $job->expires_at === null
                    || Carbon::parse($job->expires_at)->lessThanOrEqualTo(now()),
                422,
                'Isi expires_at di masa depan sebelum mengaktifkan lowongan'
            );

            $subscription = DB::table('company_subscriptions')
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->where('starts_at', '<=', now())
                ->where('expires_at', '>', now())
                ->orderByDesc('starts_at')
                ->lockForUpdate()->first();
            abort_if($subscription === null, 409, 'Subscription aktif tidak ditemukan');

            if ($subscription->source === 'purchase') {
                abort_unless(
                    DB::table('payment_transactions')
                        ->where('subscription_id', $subscription->id)
                        ->where('status', 'paid')->exists(),
                    409,
                    'Pembayaran subscription belum berstatus paid'
                );
            }

            abort_if(
                Carbon::parse($job->expires_at)->greaterThan($subscription->expires_at),
                422,
                'expires_at lowongan melewati masa subscription'
            );

            $activeCount = DB::table('job_posts')
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->whereNotNull('subscription_id')
                ->count();
            abort_if(
                $subscription->quota_total === null
                    || $activeCount >= $subscription->quota_total,
                409,
                'Kuota lowongan aktif sudah penuh'
            );

            DB::table('job_posts')->where('id', $id)->update([
                'status' => 'active',
                'subscription_id' => $subscription->id,
                'inactive_reason' => null,
                'updated_at' => now(),
            ]);
            DB::table('company_subscriptions')->where('id', $subscription->id)
                ->update(['quota_used' => $activeCount + 1, 'updated_at' => now()]);

            return response()->json([
                'remaining_quota' => $subscription->quota_total - $activeCount - 1,
                'data' => DB::table('job_posts')->where('id', $id)->first(),
            ]);
        });
    });
});
