<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StartWorkPrdApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_company_job_public_filters_and_company_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'PT Contoh', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');
        $category = 'Technology / IT';
        $jobId = $this->postJson('/api/admin/job-posts', [
            'company_id' => $companyId, 'title' => 'Data Analyst',
            'category' => $category, 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid',
        ])->assertCreated()->json('data.id');

        $this->getJson('/api/job-posts?category='.urlencode($category).'&location=Jakarta&work_type=Hybrid&job_type=Full-time&q=Analyst&sort=latest')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $jobId);
        $this->getJson("/api/job-posts/{$jobId}")->assertOk()->assertJsonPath('data.company_name', 'PT Contoh');
        $this->getJson("/api/companies/{$companyId}")->assertOk()->assertJsonPath('open_positions.0.id', $jobId);
        $this->getJson('/api/admin/companies?q=Contoh')->assertOk()->assertJsonPath('total', 1);
        $this->getJson("/api/admin/companies/{$companyId}/job-posts")->assertOk()->assertJsonPath('data.0.id', $jobId);
    }

    public function test_admin_article_crud_and_publication(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $id = $this->actingAs($admin)->postJson('/api/admin/articles', [
            'title' => 'Kiat Karier', 'content' => 'Isi artikel', 'status' => 'draft',
        ])->assertCreated()->assertJsonPath('data.author_id', $admin->id)->json('data.id');
        $this->getJson('/api/articles')->assertJsonPath('total', 0);
        $this->putJson("/api/admin/articles/{$id}", ['status' => 'published'])->assertOk();
        $this->getJson('/api/articles')->assertJsonPath('total', 1);
        $this->deleteJson("/api/admin/articles/{$id}")->assertNoContent();
        $this->getJson('/api/articles')->assertJsonPath('total', 0);
    }

    public function test_admin_applicant_name_filter_and_company_scope(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'PT Pelamar', 'location' => 'Bandung',
        ])->assertCreated()->json('data.id');
        $jobId = $this->postJson('/api/admin/job-posts', [
            'company_id' => $companyId, 'title' => 'Designer', 'location' => 'Bandung',
            'job_type' => 'Full-time', 'work_type' => 'On-site',
        ])->assertCreated()->json('data.id');
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $profileId = (string) Str::uuid();
        DB::table('jobseeker_profiles')->insert([
            'id' => $profileId, 'user_id' => $seeker->id, 'full_name' => 'Siti Pelamar',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('applications')->insert([
            'id' => (string) Str::uuid(), 'profile_id' => $profileId,
            'job_posts_id' => $jobId, 'status' => 'submitted',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->getJson('/api/admin/applicants?name=Siti')->assertJsonPath('total', 1);
        $this->getJson('/api/admin/applicants?name=TidakAda')->assertJsonPath('total', 0);
        $this->getJson("/api/admin/applicants?company_id={$companyId}")->assertJsonPath('total', 1);
    }

    public function test_package_catalog_remains_readable(): void
    {
        $this->getJson('/api/packages')->assertOk()->assertJsonStructure(['total', 'data']);
    }
}
