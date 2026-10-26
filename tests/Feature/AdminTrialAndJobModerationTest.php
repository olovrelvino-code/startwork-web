<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminTrialAndJobModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_is_admin_only_once_and_controls_employer_publication_for_30_days(): void
    {
        $this->travelTo(now()->startOfSecond());
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $companyId = $this->company($employer, 'PT Trial');
        $packageId = $this->package(2);
        $jobId = $this->actingAs($employer)->postJson('/api/employer/job-posts', [
            'title' => 'Analis', 'location' => 'Jakarta', 'job_type' => 'Full-time',
            'work_type' => 'Hybrid', 'expires_at' => now()->addDays(10)->toIso8601String(),
        ])->assertCreated()->json('data.id');

        $this->putJson("/api/employer/job-posts/{$jobId}/status", ['status' => 'active'])->assertStatus(409);
        $this->postJson("/api/admin/companies/{$companyId}/grant-trial", ['package_id' => $packageId])->assertForbidden();
        $this->actingAs($admin)->postJson("/api/admin/companies/{$companyId}/grant-trial", [
            'package_id' => $packageId, 'expires_at' => now()->addYear()->toIso8601String(),
        ])->assertUnprocessable()->assertJsonValidationErrors('expires_at');
        $this->postJson("/api/admin/companies/{$companyId}/grant-trial", ['package_id' => $packageId])
            ->assertCreated()->assertJsonPath('data.source', 'admin_trial')
            ->assertJsonPath('data.quota_total', 2);
        $subscription = DB::table('company_subscriptions')->where('company_id', $companyId)->first();
        $this->assertEqualsWithDelta(30 * 86400, strtotime($subscription->expires_at) - strtotime($subscription->starts_at), 1);
        $this->postJson("/api/admin/companies/{$companyId}/grant-trial", ['package_id' => $packageId])->assertStatus(409);
        $adminCompanyId = $this->postJson('/api/admin/companies', [
            'name' => 'Internal Admin', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');
        $this->postJson("/api/admin/companies/{$adminCompanyId}/grant-trial", ['package_id' => $packageId])->assertStatus(409);

        $this->actingAs($employer)->putJson("/api/employer/job-posts/{$jobId}/status", ['status' => 'active'])
            ->assertOk()->assertJsonPath('data.subscription_id', $subscription->id);
        $this->assertDatabaseHas('company_subscriptions', ['id' => $subscription->id, 'quota_used' => 1]);

        $this->travel(31)->days();
        $laterJob = $this->postJson('/api/employer/job-posts', [
            'title' => 'Desainer', 'location' => 'Jakarta', 'job_type' => 'Full-time',
            'work_type' => 'Hybrid', 'expires_at' => now()->addDay()->toIso8601String(),
        ])->assertCreated()->json('data.id');
        $this->putJson("/api/employer/job-posts/{$laterJob}/status", ['status' => 'active'])->assertStatus(409);
        $this->assertDatabaseHas('job_posts', ['id' => $jobId, 'company_id' => $companyId]);
        $this->travelBack();
    }

    public function test_admin_can_moderate_employer_job_but_cannot_change_ownership_or_bypass_publication(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $companyId = $this->company($employer, 'PT Moderasi');
        $jobId = $this->actingAs($employer)->postJson('/api/employer/job-posts', [
            'title' => 'Penulis', 'location' => 'Bandung', 'job_type' => 'Contract',
            'work_type' => 'Remote', 'expires_at' => now()->addDays(5)->toIso8601String(),
        ])->assertCreated()->json('data.id');

        $this->actingAs($seeker)->putJson("/api/admin/job-posts/{$jobId}", ['title' => 'Salah'])->assertForbidden();
        $this->actingAs($employer)->deleteJson("/api/admin/job-posts/{$jobId}")->assertForbidden();
        $this->actingAs($admin)->get("/admin/job-posts/{$jobId}/edit")->assertOk()->assertSee('Simpan perubahan');
        foreach (['company_id' => $companyId, 'posted_by' => $admin->id, 'subscription_id' => (string) Str::uuid(), 'status' => 'active', 'apply_url' => 'https://example.test'] as $field => $value) {
            $this->putJson("/api/admin/job-posts/{$jobId}", [$field => $value])
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->putJson("/api/admin/job-posts/{$jobId}", ['title' => 'Penulis Senior'])
            ->assertOk()->assertJsonPath('data.title', 'Penulis Senior');
        $this->putJson("/api/admin/job-posts/{$jobId}/status", ['status' => 'active'])->assertForbidden();

        $this->actingAs($employer)->putJson("/api/employer/job-posts/{$jobId}/status", ['status' => 'active'])->assertStatus(409);
        $this->actingAs($admin)->deleteJson("/api/admin/job-posts/{$jobId}")->assertNoContent();
        $this->assertDatabaseMissing('job_posts', ['id' => $jobId]);
    }

    public function test_employer_can_publish_existing_draft_after_admin_grants_trial(): void
    {
        $this->travelTo(now()->startOfSecond());
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $companyId = $this->company($employer, 'PT Kanekin');
        $jobId = $this->postJson('/api/employer/job-posts', [
            'title' => 'Content Specialist', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid',
            'expires_at' => now()->addDays(7)->toIso8601String(),
        ])->assertCreated()->json('data.id');

        $this->get("/employer/job-posts/{$jobId}")->assertOk()->assertDontSee('Terbitkan lowongan');
        $this->get('/employer/job-posts/create')->assertOk()->assertSee('Simpan draft');

        $packageId = $this->package(2);
        $this->actingAs($admin)->postJson("/api/admin/companies/{$companyId}/grant-trial", [
            'package_id' => $packageId,
        ])->assertCreated();

        $this->actingAs($employer)->get('/employer/dashboard')->assertOk()->assertSee('Trial aktif')
            ->assertSee('Lihat draft lowongan');
        $this->get('/employer/job-posts/create')->assertOk()->assertSee('Simpan dan lanjutkan');
        $this->get("/employer/job-posts/{$jobId}")->assertOk()->assertSee('Terbitkan lowongan');
        $this->putJson("/api/employer/job-posts/{$jobId}/status", ['status' => 'active'])
            ->assertOk()->assertJsonPath('data.status', 'active');
        $this->get("/employer/job-posts/{$jobId}")->assertOk()->assertDontSee('Terbitkan lowongan');

        $tooLongId = $this->postJson('/api/employer/job-posts', [
            'title' => 'Designer', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid',
            'expires_at' => now()->addDays(40)->toIso8601String(),
        ])->assertCreated()->json('data.id');
        $this->get("/employer/job-posts/{$tooLongId}")->assertOk()
            ->assertSee('Atur batas tayang')->assertDontSee('Terbitkan lowongan');
        $this->putJson("/api/employer/job-posts/{$tooLongId}", [
            'expires_at' => now()->addDays(10)->toIso8601String(),
        ])->assertOk();
        $this->get("/employer/job-posts/{$tooLongId}")->assertOk()->assertSee('Terbitkan lowongan');

        $this->travel(31)->days();
        $this->get('/employer/dashboard')->assertOk()->assertSee('Trial telah berakhir');
        $this->get('/employer/job-posts/create')->assertOk()->assertSee('Simpan draft');
        $this->get("/employer/job-posts/{$tooLongId}")->assertOk()->assertDontSee('Terbitkan lowongan');
        $this->putJson("/api/employer/job-posts/{$tooLongId}", [
            'expires_at' => now()->addDay()->toIso8601String(),
        ])->assertOk();
        $this->putJson("/api/employer/job-posts/{$tooLongId}/status", ['status' => 'active'])->assertStatus(409);
        $this->travelBack();
    }

    public function test_admin_deactivation_releases_quota_and_application_history_blocks_deletion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $companyId = $this->company($employer, 'PT Riwayat');
        $packageId = $this->package(1);
        $this->actingAs($admin)->postJson("/api/admin/companies/{$companyId}/grant-trial", ['package_id' => $packageId])->assertCreated();
        $subscriptionId = DB::table('company_subscriptions')->where('company_id', $companyId)->value('id');
        $jobId = $this->actingAs($employer)->postJson('/api/employer/job-posts', [
            'title' => 'Engineer', 'location' => 'Solo', 'job_type' => 'Full-time',
            'work_type' => 'On-site', 'expires_at' => now()->addDays(5)->toIso8601String(),
        ])->assertCreated()->json('data.id');
        $this->putJson("/api/employer/job-posts/{$jobId}/status", ['status' => 'active'])->assertOk();

        $this->actingAs($admin)->deleteJson("/api/admin/job-posts/{$jobId}")->assertStatus(409);
        $this->putJson("/api/admin/job-posts/{$jobId}/status", [
            'status' => 'inactive', 'inactive_reason' => 'Konten perlu diperbaiki',
        ])->assertOk()->assertJsonPath('data.status', 'inactive');
        $this->assertDatabaseHas('company_subscriptions', ['id' => $subscriptionId, 'quota_used' => 0]);
        $this->putJson("/api/admin/job-posts/{$jobId}/status", [
            'status' => 'inactive', 'inactive_reason' => 'Masih ditutup',
        ])->assertOk();
        $this->assertDatabaseHas('company_subscriptions', ['id' => $subscriptionId, 'quota_used' => 0]);

        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $profileId = (string) Str::uuid();
        DB::table('jobseeker_profiles')->insert([
            'id' => $profileId, 'user_id' => $seeker->id, 'full_name' => 'Pelamar',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('applications')->insert([
            'id' => (string) Str::uuid(), 'job_posts_id' => $jobId, 'profile_id' => $profileId,
            'status' => 'submitted', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->deleteJson("/api/admin/job-posts/{$jobId}")->assertStatus(409);
        $this->assertDatabaseHas('job_posts', ['id' => $jobId]);
    }

    public function test_employer_sees_full_quota_and_cannot_publish_another_draft(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $companyId = $this->company($employer, 'PT Kuota');
        $packageId = $this->package(1);
        $this->actingAs($admin)->postJson("/api/admin/companies/{$companyId}/grant-trial", [
            'package_id' => $packageId,
        ])->assertCreated();
        $this->actingAs($employer);

        $first = $this->postJson('/api/employer/job-posts', [
            'title' => 'Editor', 'location' => 'Jakarta', 'job_type' => 'Full-time',
            'work_type' => 'Hybrid', 'expires_at' => now()->addDays(7)->toIso8601String(),
        ])->assertCreated()->json('data.id');
        $this->putJson("/api/employer/job-posts/{$first}/status", ['status' => 'active'])->assertOk();
        $second = $this->postJson('/api/employer/job-posts', [
            'title' => 'Fotografer', 'location' => 'Jakarta', 'job_type' => 'Full-time',
            'work_type' => 'Hybrid', 'expires_at' => now()->addDays(7)->toIso8601String(),
        ])->assertCreated()->json('data.id');
        $this->get("/employer/job-posts/{$second}")->assertOk()->assertSee('Kuota lowongan aktif sudah penuh')
            ->assertDontSee('Terbitkan lowongan');
        $this->get('/employer/job-posts/create')->assertOk()->assertSee('kuota lowongan sedang penuh')
            ->assertSee('Simpan draft');
        $this->putJson("/api/employer/job-posts/{$second}/status", ['status' => 'active'])->assertStatus(409);
    }

    public function test_new_badge_and_company_filters_use_registration_and_trial_times_independently(): void
    {
        $this->travelTo(now()->startOfSecond());
        $admin = User::factory()->create(['role' => 'admin']);
        $packageId = $this->package(2);
        $fresh = $this->company(User::factory()->create(['role' => 'employer']), 'Fresh No Trial');
        $freshTrial = $this->company(User::factory()->create(['role' => 'employer']), 'Fresh With Trial');
        $old = $this->company(User::factory()->create(['role' => 'employer']), 'Old Expired');
        DB::table('companies')->where('id', $fresh)->update(['created_at' => now()->subMinute()]);
        DB::table('companies')->where('id', $old)->update(['created_at' => now()->subDay()]);
        $this->actingAs($admin)->postJson("/api/admin/companies/{$freshTrial}/grant-trial", ['package_id' => $packageId])->assertCreated();
        $this->postJson("/api/admin/companies/{$old}/grant-trial", ['package_id' => $packageId])->assertCreated();
        DB::table('company_subscriptions')->where('company_id', $old)->update(['expires_at' => now()->subMinute()]);
        $this->postJson('/api/admin/companies', ['name' => 'Internal Office', 'location' => 'Jakarta'])->assertCreated();

        $this->get('/admin/companies?filter=new')->assertOk()->assertSee('Fresh No Trial')->assertSee('Fresh With Trial')
            ->assertDontSee('Old Expired')->assertSee('NEW');
        $this->get('/admin/companies?filter=no_trial')->assertOk()->assertSee('Fresh No Trial')
            ->assertDontSee('Fresh With Trial')->assertDontSee('Old Expired')->assertDontSee('Internal Office');
        $this->get('/admin/companies?filter=trial_active')->assertOk()->assertSee('Fresh With Trial')
            ->assertDontSee('Old Expired');
        $this->get('/admin/companies?filter=trial_expired')->assertOk()->assertSee('Old Expired')
            ->assertDontSee('Fresh With Trial');
        $this->get("/admin/companies/{$freshTrial}")->assertOk()->assertSee('>NEW<', false)->assertSee('Trial aktif');
        $this->get("/admin/companies/{$old}")->assertOk()->assertDontSee('>NEW<', false)->assertSee('Trial berakhir');
        $html = $this->get('/admin/companies')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'Fresh No Trial'), strpos($html, 'Fresh With Trial'));
        $this->assertLessThan(strpos($html, 'Old Expired'), strpos($html, 'Fresh No Trial'));
        $this->get('/admin/dashboard')->assertOk()->assertSee('1 perusahaan baru');
        $this->travelBack();
    }

    private function company(User $owner, string $name): string
    {
        return $this->actingAs($owner)->postJson('/api/employer/company', [
            'name' => $name, 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');
    }

    private function package(int $quota): string
    {
        $id = (string) Str::uuid();
        DB::table('packages')->insert([
            'id' => $id, 'name' => 'Trial '.$id, 'quota' => $quota,
            'duration_days' => 30, 'price' => 0, 'currency' => 'IDR', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }
}
