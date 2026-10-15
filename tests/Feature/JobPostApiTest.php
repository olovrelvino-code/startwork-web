<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class JobPostApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan demo employer & demo company untuk pengujian API
        $employerId = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $employerId,
            'full_name' => 'Demo Employer',
            'email' => 'demo-employer@startwork.test',
            'role' => 'employer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('companies')->insert([
            'id' => (string) Str::uuid(),
            'owner_user_id' => $employerId,
            'created_by' => $employerId,
            'name' => 'Startwork Demo Company',
            'location' => 'Jakarta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_api_health_check_returns_database_connected(): void
    {
        $response = $this->getJson('/api');

        $response->assertStatus(200)
            ->assertJson(['database' => 'connected']);
    }

    public function test_get_job_posts_list_returns_json(): void
    {
        $response = $this->getJson('/api/job-posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'data',
            ]);
    }

    public function test_get_job_posts_form_renders_html(): void
    {
        $response = $this->get('/api/job-posts/form');

        $response->assertStatus(200)
            ->assertSee('Tambah Lowongan Kerja');
    }

    public function test_create_job_post_via_api(): void
    {
        $payload = [
            'title' => 'Senior Backend Engineer',
            'location' => 'Jakarta',
            'description' => 'Membangun REST API performa tinggi.',
            'job_type' => 'Full-time',
            'work_type' => 'Hybrid',
            'salary_min' => 12000000,
            'salary_max' => 20000000,
            'responsibilities' => 'Maintain sistem dan database.',
            'requirements' => 'Pengalaman Laravel minimal 3 tahun.',
            'benefits' => 'Asuransi kesehatan dan bonus tahunan.',
        ];

        $response = $this->postJson('/api/job-posts', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'message'])
            ->assertJson(['message' => 'created']);

        $id = $response->json('id');
        $this->assertDatabaseHas('job_posts', [
            'id' => $id,
            'title' => 'Senior Backend Engineer',
        ]);
    }

    public function test_get_single_job_post_by_id(): void
    {
        $company = DB::table('companies')->first();
        $employer = DB::table('users')->where('email', 'demo-employer@startwork.test')->first();

        $id = (string) Str::uuid();
        DB::table('job_posts')->insert([
            'id' => $id,
            'company_id' => $company->id,
            'posted_by' => $employer->id,
            'title' => 'DevOps Engineer',
            'location' => 'Bandung',
            'job_type' => 'Full-time',
            'work_type' => 'Remote',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson("/api/job-posts/{$id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $id,
                    'title' => 'DevOps Engineer',
                    'location' => 'Bandung',
                ],
            ]);
    }

    public function test_update_job_post_by_id(): void
    {
        $company = DB::table('companies')->first();
        $employer = DB::table('users')->where('email', 'demo-employer@startwork.test')->first();

        $id = (string) Str::uuid();
        DB::table('job_posts')->insert([
            'id' => $id,
            'company_id' => $company->id,
            'posted_by' => $employer->id,
            'title' => 'Junior Web Developer',
            'location' => 'Surabaya',
            'job_type' => 'Full-time',
            'work_type' => 'On-site',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->putJson("/api/job-posts/{$id}", [
            'title' => 'Mid-level Web Developer',
            'location' => 'Malang',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $id,
                    'title' => 'Mid-level Web Developer',
                    'location' => 'Malang',
                ],
            ]);
    }

    public function test_delete_job_post_by_id(): void
    {
        $company = DB::table('companies')->first();
        $employer = DB::table('users')->where('email', 'demo-employer@startwork.test')->first();

        $id = (string) Str::uuid();
        DB::table('job_posts')->insert([
            'id' => $id,
            'company_id' => $company->id,
            'posted_by' => $employer->id,
            'title' => 'Temporary QA Tester',
            'location' => 'Yogyakarta',
            'job_type' => 'Contract',
            'work_type' => 'Remote',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->deleteJson("/api/job-posts/{$id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('job_posts', ['id' => $id]);
    }

    public function test_create_job_post_validation_fails_on_missing_fields(): void
    {
        $response = $this->postJson('/api/job-posts', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'location', 'job_type', 'work_type']);
    }
}
