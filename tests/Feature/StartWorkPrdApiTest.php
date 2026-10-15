<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Feature Test Suite untuk StartWork Platform.
 * Disusun berdasarkan PRD.md, Database.md, dan Architecture.md.
 */
class StartWorkPrdApiTest extends TestCase
{
    use RefreshDatabase;

    protected string $adminId;

    protected string $employerId;

    protected string $jobseekerUserId;

    protected string $companyId;

    protected string $jobPostId;

    protected string $packageId;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed Roles & Users (users table - Database.md)
        $this->adminId = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $this->adminId,
            'full_name' => 'Internal Admin',
            'email' => 'admin@startwork.test',
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->employerId = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $this->employerId,
            'full_name' => 'Demo Employer',
            'email' => 'demo-employer@startwork.test',
            'role' => 'employer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->jobseekerUserId = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $this->jobseekerUserId,
            'full_name' => 'Budi Jobseeker',
            'email' => 'budi@startwork.test',
            'role' => 'jobseeker',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Seed Company (companies table - Database.md)
        $this->companyId = (string) Str::uuid();
        DB::table('companies')->insert([
            'id' => $this->companyId,
            'owner_user_id' => $this->employerId,
            'created_by' => $this->employerId,
            'name' => 'Tech Nusantara Inc.',
            'location' => 'Jakarta Selatan',
            'website' => 'https://technusantara.test',
            'description' => 'Perusahaan teknologi terdepan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Seed Job Post (job_posts table - Database.md)
        $this->jobPostId = (string) Str::uuid();
        DB::table('job_posts')->insert([
            'id' => $this->jobPostId,
            'company_id' => $this->companyId,
            'posted_by' => $this->employerId,
            'title' => 'Senior Backend Developer',
            'category' => 'Teknologi',
            'location' => 'Jakarta Selatan',
            'job_type' => 'Full-time',
            'work_type' => 'Hybrid',
            'salary_min' => 15000000,
            'salary_max' => 25000000,
            'description' => 'Membangun arsitektur microservices.',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Seed Packages (packages table - PRD 3.7 & Database.md)
        $this->packageId = (string) Str::uuid();
        DB::table('packages')->insert([
            'id' => $this->packageId,
            'name' => 'Standard',
            'quota' => 20,
            'duration_days' => 30,
            'price' => 149000.00,
            'currency' => 'IDR',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ==========================================
    // AREA 1: KONEKSI & DISCOVERY PUBLIK
    // ==========================================

    public function test_api_health_check_endpoint(): void
    {
        $response = $this->getJson('/api');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'database' => 'connected',
            ]);
    }

    public function test_get_job_posts_list_with_filter_category_and_location(): void
    {
        // Filter cocok
        $response = $this->getJson('/api/job-posts?category=Teknologi&location=Jakarta');
        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.title', 'Senior Backend Developer');

        // Filter tidak cocok
        $responseMiss = $this->getJson('/api/job-posts?category=Desain');
        $responseMiss->assertStatus(200)
            ->assertJsonPath('total', 0);
    }

    public function test_get_job_posts_search_query_and_sort(): void
    {
        $response = $this->getJson('/api/job-posts?q=Backend&sort=a-z');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.company_name', 'Tech Nusantara Inc.');
    }

    public function test_get_job_post_detail_includes_company_information(): void
    {
        $response = $this->getJson("/api/job-posts/{$this->jobPostId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->jobPostId)
            ->assertJsonPath('data.company_name', 'Tech Nusantara Inc.')
            ->assertJsonPath('data.company_website', 'https://technusantara.test');
    }

    public function test_get_companies_list_and_detail_with_open_positions(): void
    {
        $responseList = $this->getJson('/api/companies?q=Nusantara');
        $responseList->assertStatus(200)
            ->assertJsonPath('total', 1);

        $responseDetail = $this->getJson("/api/companies/{$this->companyId}");
        $responseDetail->assertStatus(200)
            ->assertJsonPath('data.name', 'Tech Nusantara Inc.')
            ->assertJsonCount(1, 'open_positions');
    }

    // ==========================================
    // AREA 2: FLOW EMPLOYER (PRD 3.1, 4.2)
    // ==========================================

    public function test_employer_can_post_new_job(): void
    {
        $payload = [
            'company_id' => $this->companyId,
            'posted_by' => $this->employerId,
            'title' => 'DevOps Specialist',
            'category' => 'Teknologi',
            'location' => 'Bandung',
            'job_type' => 'Full-time',
            'work_type' => 'Remote',
            'salary_min' => 12000000,
            'salary_max' => 18000000,
            'description' => 'Setup CI/CD pipeline dan server Docker.',
        ];

        $response = $this->postJson('/api/job-posts', $payload);

        $response->assertStatus(201)
            ->assertJson(['message' => 'created'])
            ->assertJsonPath('data.title', 'DevOps Specialist')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('job_posts', [
            'title' => 'DevOps Specialist',
            'company_id' => $this->companyId,
        ]);
    }

    public function test_employer_can_update_job_post(): void
    {
        $response = $this->putJson("/api/job-posts/{$this->jobPostId}", [
            'title' => 'Lead Backend Architect (Updated)',
            'location' => 'Jakarta Barat',
            'description' => 'Updated requirement',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'updated'])
            ->assertJsonPath('data.title', 'Lead Backend Architect (Updated)')
            ->assertJsonPath('data.location', 'Jakarta Barat');
    }

    public function test_employer_can_delete_job_post(): void
    {
        $response = $this->deleteJson("/api/job-posts/{$this->jobPostId}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('job_posts', ['id' => $this->jobPostId]);
    }

    // ==========================================
    // AREA 3: FLOW JOBSEEKER & LAMARAN (PRD 3.5, 3.6, 7.6)
    // ==========================================

    public function test_jobseeker_creates_profile_and_cv(): void
    {
        $payload = [
            'user_id' => $this->jobseekerUserId,
            'full_name' => 'Budi Jobseeker',
            'domicile' => 'Jakarta',
            'birth_date' => '2000-05-15',
            'gender' => 'Laki-laki',
            'last_education' => 'S1 Teknik Informatika',
            'cv_file' => 'cv/budi-cv.pdf',
            'cv_filename' => 'budi-cv.pdf',
            'portfolio_link' => 'https://budidev.test',
        ];

        $response = $this->postJson('/api/jobseeker/profile', $payload);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Profile saved'])
            ->assertJsonPath('data.user_id', $this->jobseekerUserId);

        $this->assertDatabaseHas('jobseeker_profiles', [
            'user_id' => $this->jobseekerUserId,
            'cv_file' => 'cv/budi-cv.pdf',
        ]);
    }

    public function test_jobseeker_applies_to_job_post_successfully(): void
    {
        // 1. Buat profil & CV terlebih dahulu
        $profileId = (string) Str::uuid();
        DB::table('jobseeker_profiles')->insert([
            'id' => $profileId,
            'user_id' => $this->jobseekerUserId,
            'full_name' => 'Budi Jobseeker',
            'domicile' => 'Jakarta',
            'birth_date' => '2000-05-15',
            'gender' => 'Laki-laki',
            'last_education' => 'S1',
            'cv_file' => 'cv/budi-cv.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Submit lamaran
        $response = $this->postJson("/api/job-posts/{$this->jobPostId}/apply", [
            'user_id' => $this->jobseekerUserId,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Lamaran berhasil dikirim',
                'status' => 'submitted',
            ]);

        $this->assertDatabaseHas('applications', [
            'job_posts_id' => $this->jobPostId,
            'profile_id' => $profileId,
            'status' => 'submitted',
        ]);
    }

    public function test_jobseeker_cannot_apply_twice_to_same_job_post(): void
    {
        // Setup profil
        $profileId = (string) Str::uuid();
        DB::table('jobseeker_profiles')->insert([
            'id' => $profileId,
            'user_id' => $this->jobseekerUserId,
            'full_name' => 'Budi Jobseeker',
            'domicile' => 'Jakarta',
            'birth_date' => '2000-05-15',
            'gender' => 'Laki-laki',
            'last_education' => 'S1',
            'cv_file' => 'cv/budi-cv.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Lamaran pertama
        $this->postJson("/api/job-posts/{$this->jobPostId}/apply", [
            'user_id' => $this->jobseekerUserId,
        ])->assertStatus(201);

        // Lamaran kedua ke loker yang sama (PRD 3.6 Satu lamaran per loker) -> harus 409 Conflict
        $responseDuplicate = $this->postJson("/api/job-posts/{$this->jobPostId}/apply", [
            'user_id' => $this->jobseekerUserId,
        ]);

        $responseDuplicate->assertStatus(409);
    }

    public function test_jobseeker_without_cv_cannot_apply(): void
    {
        // Setup profil tanpa CV
        DB::table('jobseeker_profiles')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $this->jobseekerUserId,
            'full_name' => 'Jobseeker Tanpa CV',
            'domicile' => 'Jakarta',
            'birth_date' => '2000-05-15',
            'gender' => 'Laki-laki',
            'last_education' => 'S1',
            'cv_file' => null, // CV kosong
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/job-posts/{$this->jobPostId}/apply", [
            'user_id' => $this->jobseekerUserId,
        ]);

        $response->assertStatus(422); // Unprocessable: CV wajib
    }

    public function test_jobseeker_views_my_applications(): void
    {
        $profileId = (string) Str::uuid();
        DB::table('jobseeker_profiles')->insert([
            'id' => $profileId,
            'user_id' => $this->jobseekerUserId,
            'full_name' => 'Budi Jobseeker',
            'domicile' => 'Jakarta',
            'birth_date' => '2000-05-15',
            'gender' => 'Laki-laki',
            'last_education' => 'S1',
            'cv_file' => 'cv/budi-cv.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('applications')->insert([
            'id' => (string) Str::uuid(),
            'job_posts_id' => $this->jobPostId,
            'profile_id' => $profileId,
            'applied_at' => now(),
            'status' => 'submitted',
            'cv_file' => 'cv/budi-cv.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson("/api/jobseeker/my-applications/{$this->jobseekerUserId}");

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.job_title', 'Senior Backend Developer')
            ->assertJsonPath('data.0.status', 'submitted');
    }

    public function test_employer_views_applicants_for_their_company(): void
    {
        $profileId = (string) Str::uuid();
        DB::table('jobseeker_profiles')->insert([
            'id' => $profileId,
            'user_id' => $this->jobseekerUserId,
            'full_name' => 'Budi Jobseeker',
            'domicile' => 'Jakarta',
            'birth_date' => '2000-05-15',
            'gender' => 'Laki-laki',
            'last_education' => 'S1',
            'cv_file' => 'cv/budi-cv.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('applications')->insert([
            'id' => (string) Str::uuid(),
            'job_posts_id' => $this->jobPostId,
            'profile_id' => $profileId,
            'applied_at' => now(),
            'status' => 'submitted',
            'cv_file' => 'cv/budi-cv.pdf',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson("/api/employer/applicants/{$this->companyId}");

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.applicant_name', 'Budi Jobseeker')
            ->assertJsonPath('data.0.job_title', 'Senior Backend Developer');
    }

    // ==========================================
    // AREA 4: ARTIKEL & BLOG (PRD 3.3, 4.3 Task 2)
    // ==========================================

    public function test_admin_creates_article_and_public_only_sees_published(): void
    {
        // Admin buat artikel published
        $this->postJson('/api/admin/articles', [
            'author_id' => $this->adminId,
            'title' => 'Panduan Menulis CV ATS-Friendly',
            'content' => 'Tips agar CV mudah dibaca sistem HRD...',
            'status' => 'published',
        ])->assertStatus(201);

        // Admin buat artikel draft
        $this->postJson('/api/admin/articles', [
            'author_id' => $this->adminId,
            'title' => 'Draft Rahasia Internal',
            'content' => 'Belum siap tayang...',
            'status' => 'draft',
        ])->assertStatus(201);

        // Publik melihat list artikel: HANYA yang published yang tampil (PRD 4.3)
        $responsePublic = $this->getJson('/api/articles');
        $responsePublic->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.title', 'Panduan Menulis CV ATS-Friendly');
    }

    // ==========================================
    // AREA 5: PACKAGES & ADMIN TRIAL (PRD 3.7, 4.3)
    // ==========================================

    public function test_packages_catalog_displays_active_packages(): void
    {
        $response = $this->getJson('/api/packages');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Standard');

        $this->assertEquals(149000, (float) $response->json('data.0.price'));
    }

    public function test_admin_grants_7_days_trial_to_company(): void
    {
        $response = $this->postJson("/api/admin/companies/{$this->companyId}/grant-trial", [
            'admin_id' => $this->adminId,
            'package_id' => $this->packageId,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Trial 7 hari berhasil diberikan',
                'duration_days' => 7,
                'quota' => 20,
            ]);

        $this->assertDatabaseHas('company_subscriptions', [
            'company_id' => $this->companyId,
            'package_id' => $this->packageId,
            'source' => 'admin_trial',
            'status' => 'active',
            'price_snapshot' => 0.00,
        ]);
    }
}
