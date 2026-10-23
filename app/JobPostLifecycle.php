<?php

namespace App;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class JobPostLifecycle
{
    public static function expireDue(): int
    {
        $ids = DB::table('job_posts')
            ->whereIn('status', ['active', 'inactive'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->pluck('id');

        $expired = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$expired): void {
                $job = DB::table('job_posts')->where('id', $id)->lockForUpdate()->first();
                if ($job === null || ! in_array($job->status, ['active', 'inactive'], true)
                    || $job->expires_at === null || Carbon::parse($job->expires_at)->greaterThan(now())) {
                    return;
                }

                DB::table('job_posts')->where('id', $id)->update([
                    'status' => 'expired',
                    'inactive_reason' => null,
                    'updated_at' => now(),
                ]);

                if ($job->status === 'active' && $job->subscription_id !== null) {
                    DB::table('company_subscriptions')
                        ->where('id', $job->subscription_id)
                        ->where('quota_used', '>', 0)
                        ->decrement('quota_used');
                }

                $expired++;
            });
        }

        return $expired;
    }
}