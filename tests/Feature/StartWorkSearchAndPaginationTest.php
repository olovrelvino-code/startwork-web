<?php

namespace Tests\Feature;

use App\JobCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StartWorkSearchAndPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_searches_each_widget_without_changing_statistics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $companyId = (string) Str::uuid();
        $profileId = (string) Str::uuid();
        $otherProfileId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'owner_user_id' => $admin->id, 'name' => 'Studio Kerja', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('jobseeker_profiles')->insert(['id' => $profileId, 'user_id' => $seeker->id, 'full_name' => 'Rina Pelamar', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('jobseeker_profiles')->insert(['id' => $otherProfileId, 'user_id' => User::factory()->create(['role' => 'jobseeker'])->id, 'full_name' => 'Sari Pelamar', 'created_at' => now(), 'updated_at' => now()]);

        foreach (['Desainer Produk' => $profileId, 'Analis Keuangan' => $otherProfileId] as $title => $applicantProfileId) {
            $jobId = (string) Str::uuid();
            DB::table('job_posts')->insert(['id' => $jobId, 'company_id' => $companyId, 'posted_by' => $admin->id, 'title' => $title, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('applications')->insert(['id' => (string) Str::uuid(), 'job_posts_id' => $jobId, 'profile_id' => $applicantProfileId, 'status' => 'submitted', 'applied_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }

        $dashboard = $this->actingAs($admin)->get('/admin/dashboard?applicants_q=Rina&jobs_q=Desainer');
        $dashboard->assertOk()->assertDontSee('dashboard-applicants-search')->assertDontSee('dashboard-jobs-search');
        $this->assertSame(2, $dashboard->viewData('counts')['applications']);
        $this->assertCount(1, $dashboard->viewData('recentApplications'));
        $this->assertCount(1, $dashboard->viewData('recentJobs'));
        $this->get('/admin/dashboard?applicants_q=TidakAda&jobs_q=TidakAda')->assertOk()
            ->assertSee('Lamaran tidak ditemukan')->assertSee('Lowongan tidak ditemukan');
        $this->getJson('/admin/dashboard?jobs_q='.str_repeat('x', 256))->assertUnprocessable()->assertJsonValidationErrors('jobs_q');
        $this->actingAs(User::factory()->create(['role' => 'employer']))->get('/admin/dashboard?jobs_q=Desainer')->assertForbidden();
    }

    public function test_admin_job_date_filter_includes_entire_last_day_and_rejects_reversed_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'owner_user_id' => $admin->id, 'name' => 'Studio Tanggal', 'created_at' => now(), 'updated_at' => now()]);
        foreach (['Awal' => '2026-10-01 00:00:00', 'Akhir' => '2026-10-02 23:59:59', 'Di luar' => '2026-10-03 00:00:00'] as $title => $createdAt) {
            DB::table('job_posts')->insert(['id' => (string) Str::uuid(), 'company_id' => $companyId, 'posted_by' => $admin->id, 'title' => $title, 'status' => 'active', 'created_at' => $createdAt, 'updated_at' => $createdAt]);
        }

        $response = $this->actingAs($admin)->get('/admin/job-posts?date_from=2026-10-01&date_to=2026-10-02');
        $response->assertOk()->assertSee('2 lowongan ditemukan')->assertSee('Reset tanggal');
        $this->assertSame(2, $response->viewData('jobs')->total());
        $this->get('/admin/job-posts?date_to=2026-10-01')->assertOk()->assertSee('1 lowongan ditemukan');
        $this->getJson('/admin/job-posts?date_from=2026-10-03&date_to=2026-10-02')->assertUnprocessable()->assertJsonValidationErrors('date_to');
        $this->getJson('/admin/job-posts?date_from=2026-99-01')->assertUnprocessable()->assertJsonValidationErrors('date_from');
    }

    public function test_admin_cv_link_uses_protected_download_route(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $companyId = (string) Str::uuid();
        $jobId = (string) Str::uuid();
        $profileId = (string) Str::uuid();
        $applicationId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'owner_user_id' => $employer->id, 'name' => 'Studio CV', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('job_posts')->insert(['id' => $jobId, 'company_id' => $companyId, 'posted_by' => $employer->id, 'title' => 'Editor CV', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('jobseeker_profiles')->insert(['id' => $profileId, 'user_id' => $seeker->id, 'full_name' => 'Rina CV', 'cv_filename' => 'resume.pdf', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('applications')->insert(['id' => $applicationId, 'job_posts_id' => $jobId, 'profile_id' => $profileId, 'status' => 'submitted', 'cv_file' => 'cvs/resume.pdf', 'applied_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        Storage::disk('local')->put('cvs/resume.pdf', '%PDF-1.4');

        $this->actingAs($admin)->get('/admin/applicants')->assertOk()->assertSee('aria-label="Unduh CV Rina CV"', false);
        $this->get('/api/admin/applicants/'.$applicationId.'/cv')->assertDownload('resume.pdf');
        $this->get('/api/admin/applicants/'.$applicationId.'/cv?preview=1')->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($employer)->get('/api/admin/applicants/'.$applicationId.'/cv')->assertForbidden();
    }

    public function test_applicant_detail_and_certificate_files_are_scoped_to_admin_and_own_employer(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $otherEmployer = User::factory()->create(['role' => 'employer']);
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $companyId = (string) Str::uuid();
        $jobId = (string) Str::uuid();
        $profileId = (string) Str::uuid();
        $applicationId = (string) Str::uuid();
        $certificateId = (string) Str::uuid();
        $certificatePath = 'certificates/'.$seeker->id.'/'.Str::uuid().'.pdf';
        DB::table('companies')->insert(['id' => $companyId, 'owner_user_id' => $employer->id, 'name' => 'Studio Sertifikat', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('job_posts')->insert(['id' => $jobId, 'company_id' => $companyId, 'posted_by' => $employer->id, 'title' => 'Editor', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('jobseeker_profiles')->insert(['id' => $profileId, 'user_id' => $seeker->id, 'full_name' => 'Rina Sertifikat', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('applications')->insert(['id' => $applicationId, 'job_posts_id' => $jobId, 'profile_id' => $profileId, 'status' => 'submitted', 'applied_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('certificates')->insert(['id' => $certificateId, 'profile_id' => $profileId, 'name' => 'Sertifikat SQL', 'file_path' => $certificatePath]);
        Storage::disk('local')->put($certificatePath, '%PDF-1.4 certificate');
        $fileUrl = '/api/applications/'.$applicationId.'/certificates/'.$certificateId.'/file';

        $this->actingAs($admin)->get('/admin/applicants')->assertOk()
            ->assertSee(route('admin.applicants.show', $applicationId), false);
        $this->get('/admin/applicants/'.$applicationId)->assertOk()
            ->assertSee('Rina Sertifikat')->assertSee('Sertifikat SQL')->assertSee($fileUrl, false)
            ->assertDontSee('data-employer-form', false);
        $this->get($fileUrl.'?preview=1')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get($fileUrl)->assertDownload('Sertifikat SQL.pdf');

        $this->actingAs($employer)->get('/employer/applications/'.$applicationId)->assertOk()
            ->assertSee('Sertifikat SQL')->assertSee($fileUrl, false);
        $this->get($fileUrl)->assertDownload('Sertifikat SQL.pdf');
        $this->actingAs($otherEmployer)->get('/employer/applications/'.$applicationId)->assertNotFound();
        $this->get($fileUrl)->assertNotFound();
        $this->get('/admin/applicants/'.$applicationId)->assertForbidden();
        $this->actingAs($seeker)->get($fileUrl)->assertForbidden();
        $this->actingAs($admin)->get('/admin/applicants/'.Str::uuid())->assertNotFound();
        $this->get('/admin/applicants/'.$applicationId)->assertOk()
            ->assertDontSee($certificatePath, false);
        $this->get('/api/applications/'.$applicationId.'/certificates/'.Str::uuid().'/file')->assertNotFound();
        Storage::disk('local')->delete($certificatePath);
        $this->get($fileUrl)->assertNotFound();
        $wrongOwnerPath = 'certificates/'.$otherEmployer->id.'/'.Str::uuid().'.pdf';
        DB::table('certificates')->where('id', $certificateId)->update(['file_path' => $wrongOwnerPath]);
        Storage::disk('local')->put($wrongOwnerPath, '%PDF-1.4 certificate');
        $this->get($fileUrl)->assertNotFound();
    }

    public function test_categories_are_shared_and_legacy_jobs_remain_filterable(): void
    {
        $this->assertSame([
            'Hospitality & F&B', 'Administration & Support', 'Sales & Marketing',
            'Logistics & Operations', 'Creative & Media', 'Retail Operations',
            'HR & People', 'Finance & Audit', 'Security', 'Service',
            'Technology/IT', 'Healthy & Beauty', 'Others',
        ], JobCategory::PREDEFINED);

        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $seeker = User::factory()->create(['role' => 'jobseeker']);

        $this->getJson('/api/job-categories')->assertUnauthorized();
        $this->actingAs($seeker)->getJson('/api/job-categories')->assertForbidden();
        $this->actingAs($employer)->getJson('/api/job-categories')->assertOk()
            ->assertJsonCount(13, 'data')->assertJsonPath('data.10.name', 'Technology/IT');
        $this->get('/api/job-posts/form')->assertOk()->assertSee('<select id="category" name="category">', false)
            ->assertSee('Technology/IT')->assertSee('Healthy &amp; Beauty', false);
        $this->postJson('/api/job-categories', ['name' => 'Data Science'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');

        $this->postJson('/api/employer/company', ['name' => 'Studio Kategori', 'location' => 'Jakarta'])->assertCreated();
        $jobId = $this->postJson('/api/employer/job-posts', [
            'title' => 'Analis', 'location' => 'Jakarta', 'job_type' => 'Full-time',
            'work_type' => 'Hybrid', 'category' => 'Technology/IT',
        ])->assertCreated()->json('data.id');
        $this->assertDatabaseHas('job_posts', ['id' => $jobId, 'category' => 'Technology/IT']);
        $this->postJson('/api/employer/job-posts', [
            'title' => 'Kategori bebas', 'location' => 'Jakarta', 'job_type' => 'Full-time',
            'work_type' => 'Hybrid', 'category' => 'Data Science',
        ])->assertUnprocessable()->assertJsonValidationErrors('category');

        DB::table('job_posts')->where('id', $jobId)->update(['category' => 'Technology / IT', 'status' => 'active']);
        $this->get('/employer/job-posts/'.$jobId.'/edit')->assertOk()->assertSee('Technology / IT (kategori tersimpan)');
        $this->putJson('/api/employer/job-posts/'.$jobId, ['title' => 'Analis Senior'])->assertOk();
        $this->assertDatabaseHas('job_posts', ['id' => $jobId, 'category' => 'Technology / IT']);
        $this->getJson('/api/job-posts?category='.urlencode('Technology/IT'))
            ->assertOk()->assertJsonPath('data.0.id', $jobId);
        $this->putJson('/api/employer/job-posts/'.$jobId, ['category' => 'Healthy & Beauty'])
            ->assertOk()->assertJsonPath('data.category', 'Healthy & Beauty');

        $companyId = $this->actingAs($admin)->postJson('/api/admin/companies', ['name' => 'Studio Admin', 'location' => 'Jakarta'])
            ->assertCreated()->json('data.id');
        $this->get('/admin/job-posts/create')->assertOk()
            ->assertSee('value="Technology/IT"', false)
            ->assertSee('value="Healthy &amp; Beauty"', false);
        $adminJobId = $this->postJson('/api/admin/job-posts', [
            'company_id' => $companyId, 'title' => 'Admin Analyst', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid', 'category' => 'Technology/IT',
        ])->assertCreated()->json('data.id');
        $this->assertDatabaseHas('job_posts', ['id' => $adminJobId, 'category' => 'Technology/IT']);
        $this->putJson('/api/admin/job-posts/'.$adminJobId, ['category' => 'Data Science'])
            ->assertUnprocessable()->assertJsonValidationErrors('category');
        $this->actingAs($seeker)->postJson('/api/job-categories', ['name' => 'Others'])->assertForbidden();
    }

    public function test_admin_creates_a_job_from_company_detail_without_editable_company_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'Perusahaan Utama', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');
        $otherCompanyId = $this->postJson('/api/admin/companies', [
            'name' => 'Perusahaan Kedua', 'location' => 'Bandung',
        ])->assertCreated()->json('data.id');
        $foreignCompanyId = $this->actingAs($otherAdmin)->postJson('/api/admin/companies', [
            'name' => 'Perusahaan Lain', 'location' => 'Surabaya',
        ])->assertCreated()->json('data.id');

        $createUrl = route('admin.companies.job-posts.create', $companyId);
        $postUrl = '/api/admin/companies/'.$companyId.'/job-posts';
        $this->actingAs($admin)->get(route('admin.companies.show', $companyId))->assertOk()
            ->assertSee('Belum ada lowongan')->assertSee($createUrl)->assertSee('Buat Lowongan');
        $this->get($createUrl)->assertOk()->assertSee('Perusahaan Utama')
            ->assertSee('action="'.url($postUrl).'"', false)
            ->assertSee('data-success-url="'.route('admin.companies.show', $companyId).'"', false)
            ->assertDontSee('name="company_id"', false);

        $input = [
            'title' => 'Lowongan dari detail', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid', 'category' => 'Others',
        ];
        $jobId = $this->postJson($postUrl, $input)->assertCreated()->json('data.id');
        $this->assertDatabaseHas('job_posts', [
            'id' => $jobId, 'company_id' => $companyId, 'posted_by' => $admin->id,
        ]);
        $this->get(route('admin.companies.show', $companyId))->assertOk()
            ->assertSee('Lowongan dari detail')->assertSee($createUrl)->assertSee('Buat Lowongan');

        $this->postJson($postUrl, $input + ['company_id' => $otherCompanyId])
            ->assertUnprocessable()->assertJsonValidationErrors('company_id');
        $this->get(route('admin.companies.job-posts.create', $foreignCompanyId))->assertNotFound();
        $this->postJson('/api/admin/companies/'.$foreignCompanyId.'/job-posts', $input)->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'employer']))->postJson($postUrl, $input)->assertForbidden();
    }

    public function test_company_search_limits_results_to_admin_owned_companies(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'admin']);
        $ownedCompanyIds = [];
        foreach (range(1, 17) as $number) {
            $ownedCompanyIds[$number] = (string) Str::uuid();
            DB::table('companies')->insert([
                'id' => $ownedCompanyIds[$number], 'owner_user_id' => $admin->id,
                'name' => sprintf('Studio %02d', $number), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('companies')->insert([
            'id' => (string) Str::uuid(), 'owner_user_id' => $other->id,
            'name' => 'Studio Asing', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($admin)->getJson('/admin/job-posts/company-options')
            ->assertOk()->assertJsonCount(10, 'data')->assertDontSee('Studio Asing');
        $this->getJson('/admin/job-posts/company-options?q=Studio%2017')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Studio 17');
        $this->get('/admin/job-posts/create')->assertOk()->assertSee('Ketik nama perusahaan')->assertDontSee('Studio 17');
        $this->getJson('/admin/job-posts/company-options?scope=all&q=Studio%20Asing')
            ->assertOk()->assertJsonPath('data.0.name', 'Studio Asing');
        foreach ([1 => 'Analyst Alpha', 17 => 'Analyst Beta'] as $number => $title) {
            $this->postJson('/api/admin/job-posts', [
                'company_id' => $ownedCompanyIds[$number], 'title' => $title,
                'location' => 'Jakarta', 'job_type' => 'Full-time', 'work_type' => 'Hybrid',
            ])->assertCreated();
        }
        $this->get('/admin/job-posts?company=Studio%2017')->assertOk()
            ->assertSee('Analyst Beta')->assertDontSee('Analyst Alpha')
            ->assertSee('aria-label="Ubah lowongan Analyst Beta"', false)
            ->assertSee('name="company"', false)->assertDontSee('job-company-filter-options');
        $this->get('/admin/job-posts?company_id='.$ownedCompanyIds[1])->assertOk()
            ->assertSee('Analyst Alpha')->assertDontSee('Analyst Beta');
        $this->getJson('/admin/job-posts?company='.str_repeat('a', 256))->assertUnprocessable()
            ->assertJsonValidationErrors('company');
        $this->actingAs(User::factory()->create(['role' => 'employer']))
            ->getJson('/admin/job-posts/company-options')->assertForbidden();
    }

    public function test_admin_company_list_paginates_ten_and_keeps_sequence(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (range(1, 17) as $number) {
            DB::table('companies')->insert([
                'id' => (string) Str::uuid(), 'owner_user_id' => $admin->id,
                'name' => sprintf('Company %02d', $number), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $first = $this->actingAs($admin)->get('/admin/companies?q=Company');
        $first->assertOk()->assertSee('No.')->assertSee('Page 1 of 2')
            ->assertSee('rounded-full border border-[var(--admin-brand)] bg-[var(--admin-brand)]', false)
            ->assertSee('aria-current="page" aria-label="Halaman 1"', false)
            ->assertDontSee('>Sebelumnya<', false)->assertDontSee('>Berikutnya<', false);
        $this->assertSame(10, $first->viewData('companies')->count());
        $this->assertStringContainsString('q=Company', $first->viewData('companies')->url(2));
        $second = $this->get('/admin/companies?q=Company&page=2');
        $second->assertOk()->assertSee('No.')->assertSee('q=Company', false)->assertSee('Page 2 of 2')
            ->assertSee('aria-current="page" aria-label="Halaman 2"', false);
        $this->assertSame(11, $second->viewData('companies')->firstItem());
        $this->assertSame(7, $second->viewData('companies')->count());
        $this->get('/admin/companies?q=Company%2017')->assertOk()
            ->assertSee('Page 1 of 1')->assertSee('Company 17')->assertDontSee('Company 01');
    }

    public function test_admin_article_list_paginates_ten_and_shows_empty_page_indicator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (range(1, 21) as $number) {
            DB::table('articles')->insert([
                'id' => (string) Str::uuid(), 'author_id' => $admin->id,
                'title' => 'Artikel '.$number, 'status' => 'draft',
                'created_at' => now()->subSeconds($number), 'updated_at' => now(),
            ]);
        }

        $first = $this->actingAs($admin)->get('/admin/articles?status=draft');
        $first->assertOk()->assertSee('Page 1 of 3');
        $this->assertSame(10, $first->viewData('articles')->count());
        $second = $this->get('/admin/articles?status=draft&page=2');
        $second->assertOk()->assertSee('Page 2 of 3')->assertSee('status=draft', false);
        $this->assertSame(11, $second->viewData('articles')->firstItem());
        $last = $this->get('/admin/articles?status=draft&page=3');
        $last->assertOk()->assertSee('Page 3 of 3');
        $this->assertSame(1, $last->viewData('articles')->count());

        $empty = $this->get('/admin/articles?status=published');
        $empty->assertOk()->assertSee('Page 1 of 1')->assertSee('0–0 dari 0');
    }

    public function test_admin_package_list_paginates_ten_and_keeps_sequence(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (range(1, 11) as $number) {
            DB::table('packages')->insert([
                'id' => (string) Str::uuid(), 'name' => 'Paket '.$number,
                'quota' => 1, 'duration_days' => 30, 'price' => $number,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $first = $this->actingAs($admin)->get('/admin/packages');
        $first->assertOk()->assertSee('Page 1 of 2');
        $this->assertSame(10, $first->viewData('packages')->count());
        $second = $this->get('/admin/packages?page=2');
        $second->assertOk()->assertSee('Page 2 of 2');
        $this->assertSame(11, $second->viewData('packages')->firstItem());
        $this->assertSame(1, $second->viewData('packages')->count());
    }

    public function test_admin_pagination_keeps_large_page_ranges_compact(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (range(1, 61) as $number) {
            DB::table('packages')->insert([
                'id' => (string) Str::uuid(), 'name' => 'Paket '.$number,
                'quota' => 1, 'duration_days' => 30, 'price' => $number,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $first = $this->actingAs($admin)->get('/admin/packages');
        $first->assertOk()->assertSee('Page 1 of 7')->assertSee('aria-label="Halaman 4"', false)
            ->assertSee('aria-label="Halaman 7"', false)->assertSee('…')
            ->assertDontSee('aria-label="Halaman 5"', false);
        $middle = $this->get('/admin/packages?page=4');
        $middle->assertOk()->assertSee('Page 4 of 7')
            ->assertSee('aria-current="page" aria-label="Halaman 4"', false)
            ->assertSee('aria-label="Halaman 7"', false);
        $this->assertSame(31, $middle->viewData('packages')->firstItem());
        $last = $this->get('/admin/packages?page=7');
        $last->assertOk()->assertSee('Page 7 of 7')
            ->assertSee('aria-current="page" aria-label="Halaman 7"', false);
        $this->assertSame(61, $last->viewData('packages')->firstItem());
    }

    public function test_admin_dashboard_shows_five_latest_rows_and_full_lists_paginate_ten(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $companyId = (string) Str::uuid();
        $profileId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'owner_user_id' => $admin->id,
            'name' => 'Company Dashboard', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('jobseeker_profiles')->insert(['id' => $profileId, 'user_id' => $seeker->id,
            'full_name' => 'Pelamar Dashboard', 'created_at' => now(), 'updated_at' => now()]);
        foreach (range(1, 21) as $number) {
            $jobId = (string) Str::uuid();
            DB::table('job_posts')->insert(['id' => $jobId, 'company_id' => $companyId,
                'posted_by' => $admin->id, 'title' => 'Lowongan '.$number, 'status' => 'active',
                'created_at' => now()->subSeconds($number), 'updated_at' => now()]);
            DB::table('applications')->insert(['id' => (string) Str::uuid(), 'job_posts_id' => $jobId,
                'profile_id' => $profileId, 'status' => 'submitted', 'applied_at' => now()->subSeconds($number),
                'created_at' => now(), 'updated_at' => now()]);
        }

        $dashboard = $this->actingAs($admin)->get('/admin/dashboard');
        $dashboard->assertOk()->assertSee('Lowongan 1')->assertDontSee('Lowongan 6')
            ->assertDontSee('jobs_page=2', false)->assertDontSee('applications_page=2', false);
        $this->assertSame(5, $dashboard->viewData('recentJobs')->count());
        $this->assertSame(5, $dashboard->viewData('recentApplications')->count());

        $jobs = $this->get('/admin/job-posts?status=active');
        $jobs->assertOk()->assertSee('Page 1 of 3')->assertSee('1–10 dari 21');
        $this->assertSame(10, $jobs->viewData('jobs')->count());
        $jobsPageTwo = $this->get('/admin/job-posts?status=active&page=2');
        $jobsPageTwo->assertOk()->assertSee('Page 2 of 3')->assertSee('11–20 dari 21');
        $this->assertSame(11, $jobsPageTwo->viewData('jobs')->firstItem());
        $jobsLastPage = $this->get('/admin/job-posts?status=active&page=3');
        $jobsLastPage->assertOk()->assertSee('Page 3 of 3')->assertSee('21–21 dari 21');
        $this->assertSame(1, $jobsLastPage->viewData('jobs')->count());

        $applicants = $this->get('/admin/applicants?q=Pelamar');
        $applicants->assertOk()->assertSee('Page 1 of 3');
        $this->assertSame(10, $applicants->viewData('applicants')->count());
        $applicantsPageTwo = $this->get('/admin/applicants?q=Pelamar&page=2');
        $applicantsPageTwo->assertOk()->assertSee('Page 2 of 3')->assertSee('q=Pelamar', false);
        $this->assertSame(11, $applicantsPageTwo->viewData('applicants')->firstItem());
        $applicantsLastPage = $this->get('/admin/applicants?q=Pelamar&page=3');
        $applicantsLastPage->assertOk()->assertSee('Page 3 of 3');
        $this->assertSame(1, $applicantsLastPage->viewData('applicants')->count());
    }

    public function test_employer_dashboard_lists_paginate_ten_and_keep_company_scope(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $other = User::factory()->create(['role' => 'employer']);
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $companyId = (string) Str::uuid();
        $otherCompanyId = (string) Str::uuid();
        $profileId = (string) Str::uuid();
        DB::table('companies')->insert([
            ['id' => $companyId, 'owner_user_id' => $employer->id, 'name' => 'Studio Rekrut', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $otherCompanyId, 'owner_user_id' => $other->id, 'name' => 'Studio Asing', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('jobseeker_profiles')->insert(['id' => $profileId, 'user_id' => $seeker->id,
            'full_name' => 'Pelamar Dashboard', 'created_at' => now(), 'updated_at' => now()]);
        foreach (range(1, 22) as $number) {
            $jobId = (string) Str::uuid();
            $isOther = $number === 22;
            DB::table('job_posts')->insert(['id' => $jobId,
                'company_id' => $isOther ? $otherCompanyId : $companyId,
                'posted_by' => $isOther ? $other->id : $employer->id,
                'title' => $isOther ? 'Lowongan Asing' : 'Lowongan '.$number,
                'status' => 'draft', 'created_at' => now()->subSeconds($number), 'updated_at' => now()]);
            DB::table('applications')->insert(['id' => (string) Str::uuid(), 'job_posts_id' => $jobId,
                'profile_id' => $profileId, 'status' => 'submitted',
                'applied_at' => now()->subSeconds($number), 'created_at' => now(), 'updated_at' => now()]);
        }

        $first = $this->actingAs($employer)->get('/employer/dashboard');
        $first->assertOk()->assertSee('Halaman 1 dari 3')->assertSee('1–10 dari 21')
            ->assertDontSee('Lowongan Asing')
            ->assertSee('aria-current="page" aria-label="Halaman 1"', false)
            ->assertSee('aria-label="Halaman 2"', false)
            ->assertSee('aria-label="Halaman sebelumnya"', false);
        $this->assertSame(10, $first->viewData('jobs')->count());
        $this->assertSame(10, $first->viewData('applications')->count());
        $this->assertSame(21, $first->viewData('jobs')->total());
        $this->assertSame(21, $first->viewData('applications')->total());

        $jobsOnly = $this->get('/employer/dashboard?jobs_page=2');
        $jobsOnly->assertOk();
        $this->assertSame(11, $jobsOnly->viewData('jobs')->firstItem());
        $this->assertSame(1, $jobsOnly->viewData('applications')->firstItem());

        $second = $this->get('/employer/dashboard?jobs_page=2&applications_page=2');
        $second->assertOk()->assertSee('11.')->assertSee('Halaman 2 dari 3')
            ->assertSee('11–20 dari 21');
        $this->assertStringContainsString('applications_page=2', $second->viewData('jobs')->nextPageUrl());
        $this->assertSame(10, $second->viewData('jobs')->count());
        $this->assertSame(10, $second->viewData('applications')->count());

        $last = $this->get('/employer/dashboard?jobs_page=3&applications_page=3');
        $last->assertOk()->assertSee('21.')->assertSee('Halaman 3 dari 3')
            ->assertSee('21–21 dari 21')
            ->assertSee('aria-current="page" aria-label="Halaman 3"', false)
            ->assertSee('aria-label="Halaman berikutnya"', false);
        $this->assertSame(1, $last->viewData('jobs')->count());
        $this->assertSame(1, $last->viewData('applications')->count());

        $jobsList = $this->get('/employer/job-posts?page=2')->assertOk()
            ->assertSee('Halaman 2 dari 2')->assertSee('aria-current="page" aria-label="Halaman 2"', false);
        $this->assertSame(16, $jobsList->viewData('jobs')->firstItem());
        $applicationsList = $this->get('/employer/applications?page=2')->assertOk()
            ->assertSee('Halaman 2 dari 2')->assertSee('aria-current="page" aria-label="Halaman 2"', false);
        $this->assertSame(16, $applicationsList->viewData('applications')->firstItem());
    }

    public function test_empty_dashboard_lists_keep_pagination_controls_disabled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $adminPage = $this->actingAs($admin)->get('/admin/dashboard');
        $adminPage->assertOk()->assertSee('Belum ada lamaran')->assertSee('Belum ada lowongan')
            ->assertDontSee('Navigasi halaman');

        $employer = User::factory()->create(['role' => 'employer']);
        DB::table('companies')->insert(['id' => (string) Str::uuid(), 'owner_user_id' => $employer->id,
            'name' => 'Studio Baru', 'created_at' => now(), 'updated_at' => now()]);
        $employerPage = $this->actingAs($employer)->get('/employer/dashboard');
        $employerPage->assertOk()->assertSee('Belum ada lamaran')->assertSee('Belum ada lowongan')
            ->assertSee('0–0 dari 0')->assertSee('Halaman 1 dari 1')
            ->assertSee('aria-disabled="true"', false);
    }

    public function test_applicant_search_and_profile_details_respect_employer_scope(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $otherEmployer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $companyId = (string) Str::uuid();
        $jobId = (string) Str::uuid();
        $profileId = (string) Str::uuid();
        $applicationId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'owner_user_id' => $employer->id,
            'name' => 'Studio Rekrut', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('job_posts')->insert(['id' => $jobId, 'company_id' => $companyId, 'posted_by' => $employer->id,
            'title' => 'Analis Data', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('jobseeker_profiles')->insert(['id' => $profileId, 'user_id' => $seeker->id,
            'full_name' => 'Siti Pelamar', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('applications')->insert(['id' => $applicationId, 'job_posts_id' => $jobId,
            'profile_id' => $profileId, 'status' => 'submitted', 'applied_at' => now(),
            'created_at' => now(), 'updated_at' => now()]);
        DB::table('work_experiences')->insert(['id' => (string) Str::uuid(), 'profile_id' => $profileId,
            'job_title' => 'Data Analyst', 'company' => 'PT Contoh']);
        DB::table('organization_experiences')->insert(['id' => (string) Str::uuid(), 'profile_id' => $profileId,
            'organization_name' => 'Komunitas Data', 'role' => 'Ketua']);
        DB::table('skills')->insert(['id' => (string) Str::uuid(), 'profile_id' => $profileId, 'name' => 'SQL']);
        DB::table('certificates')->insert(['id' => (string) Str::uuid(), 'profile_id' => $profileId,
            'name' => 'Sertifikat Analitik', 'file_url' => 'private/certificate.pdf']);

        $this->actingAs($employer)->get('/employer/applications?q=Siti')->assertOk()->assertSee('Siti Pelamar');
        $this->get('/employer/applications?q=Tidak%20Ada')->assertOk()->assertDontSee('Siti Pelamar');
        $this->get('/employer/applications/'.$applicationId)->assertOk()
            ->assertSee('Data Analyst')->assertSee('Komunitas Data')->assertSee('SQL')
            ->assertSee('Sertifikat Analitik')->assertDontSee('private/certificate.pdf');
        $this->actingAs($admin)->get('/admin/applicants?q=Analis')->assertOk()->assertSee('Siti Pelamar');
        $this->get('/admin/applicants?q=Tidak%20Ada')->assertOk()->assertDontSee('Siti Pelamar');
        $this->actingAs($otherEmployer)->get('/employer/applications?q=Siti')->assertOk()->assertDontSee('Siti Pelamar');
        $this->get('/employer/applications/'.$applicationId)->assertNotFound();
        $this->actingAs($seeker)->get('/employer/applications/'.$applicationId)->assertForbidden();

        foreach (['work_experiences', 'organization_experiences', 'skills', 'certificates'] as $table) {
            DB::table($table)->where('profile_id', $profileId)->delete();
        }
        $this->actingAs($employer)->get('/employer/applications/'.$applicationId)->assertOk()
            ->assertSee('Pengalaman kerja belum diisi.')
            ->assertSee('Pengalaman organisasi belum diisi.')
            ->assertSee('Keahlian belum diisi.')
            ->assertSee('Sertifikat belum diisi.');
    }
}
