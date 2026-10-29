<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class JobseekerFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_sections_use_only_active_jobs_and_real_companies(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'name' => 'PT Karier Nyata', 'location' => 'Bandung', 'logo' => 'logo.webp', 'created_at' => now(), 'updated_at' => now()]);
        foreach (['active', 'inactive'] as $status) {
            DB::table('job_posts')->insert([
                'id' => (string) Str::uuid(), 'company_id' => $companyId, 'posted_by' => $admin->id,
                'title' => "Posisi {$status}", 'category' => 'Technology / IT', 'status' => $status,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $response = $this->get('/')->assertOk()->assertSee('PT Karier Nyata')->assertSee('Technology / IT')
            ->assertSee('1 lowongan aktif')->assertSee('Posisi active')->assertDontSee('Posisi inactive')
            ->assertSee(url('/api/companies/'.$companyId.'/logo'))
            ->assertSee('Data demo')
            ->assertSee('Cari nama atau judul lowongan')
            ->assertSee('Kategori lowongan aktif')
            ->assertSee('action="'.route('jobs.index').'"', false)
            ->assertSee(route('jobs.index', ['category' => 'Technology / IT']))
            ->assertSee('Cari')
            ->assertSee('Registrasi Perusahaan')
            ->assertSee(route('employer.register'))
            ->assertDontSee('Asisten AI');
        $this->assertSame(2, substr_count($response->getContent(), url('/api/companies/'.$companyId.'/logo')));
        $this->assertSame(1, substr_count($response->getContent(), 'id="popular-categories-title"'));
        $this->getJson('/api/job-posts?category='.urlencode('Technology / IT'))
            ->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/job-posts?q=Posisi')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_registration_keeps_jobseeker_identity_and_phone(): void
    {
        $this->post('/register', [
            'name' => 'Pencari Kerja', 'email' => 'pencari@example.test', 'phone' => '08123456789',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect('/loker');

        $this->assertDatabaseHas('users', [
            'email' => 'pencari@example.test', 'phone' => '08123456789', 'role' => 'jobseeker',
        ]);
        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.role', 'jobseeker');
    }

    public function test_profile_details_endpoint_replaces_lists_so_edits_must_send_existing_entries(): void
    {
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $this->actingAs($seeker)->postJson('/api/jobseeker/profile', [
            'full_name' => 'Pencari Kerja', 'domicile' => 'Bandung', 'birth_date' => '2000-01-01',
            'gender' => 'female', 'last_education' => 'S1',
        ])->assertCreated();

        $details = [
            'skills' => ['SQL'],
            'certificates' => [['name' => 'Sertifikat SQL', 'file_url' => 'https://example.test/sql']],
        ];
        $this->postJson('/api/jobseeker/profile/details', $details)->assertOk();
        $this->putJson('/api/jobseeker/profile/details', ['skills' => ['SQL', 'Laravel'], 'certificates' => $details['certificates']])->assertOk();

        $this->getJson('/api/jobseeker/profile/details')->assertOk()
            ->assertJsonCount(2, 'data.skills')
            ->assertJsonCount(1, 'data.certificates');
        $this->assertSame(1, DB::table('certificates')->count());
    }

    public function test_public_pages_render_and_private_pages_enforce_jobseeker_role(): void
    {
        foreach (['/', '/loker', '/perusahaan', '/about-us', '/faqs', '/articles'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->get('/blog')->assertRedirect('/articles');
        $this->get('/profil')->assertRedirect('/login');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/profil')->assertForbidden();
        $this->get('/profil/lamaran')->assertForbidden();

        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $this->actingAs($seeker)->get('/profil')->assertOk()->assertSee('Profil & CV');
        $this->get('/profil/lamaran')->assertOk()->assertSee('Lamaran Saya');
    }

    public function test_company_detail_exposes_only_open_jobs_for_public_cards(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = (string) Str::uuid();
        DB::table('companies')->insert([
            'id' => $companyId, 'name' => 'PT Buka Lowongan', 'logo' => 'logo.webp',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['active', 'inactive'] as $status) {
            DB::table('job_posts')->insert([
                'id' => (string) Str::uuid(), 'company_id' => $companyId, 'posted_by' => $admin->id,
                'title' => "Posisi {$status}", 'job_type' => 'Full-time', 'work_type' => 'Hybrid',
                'status' => $status, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->get('/perusahaan/'.$companyId)->assertOk()
            ->assertSee('Lowongan Terbuka')->assertSee('company-open-job-card');
        $this->getJson('/api/companies/'.$companyId)->assertOk()
            ->assertJsonPath('data.name', 'PT Buka Lowongan')
            ->assertJsonPath('data.logo', 'logo.webp')
            ->assertJsonCount(1, 'open_positions')
            ->assertJsonPath('open_positions.0.title', 'Posisi active')
            ->assertJsonPath('open_positions.0.job_type', 'Full-time');
        $this->get('/employer/register')->assertOk();
    }

    public function test_public_job_pagination_reports_full_total_and_distinct_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'name' => 'PT Uji', 'created_at' => now(), 'updated_at' => now()]);
        for ($index = 0; $index < 17; $index++) {
            DB::table('job_posts')->insert([
                'id' => (string) Str::uuid(), 'company_id' => $companyId, 'posted_by' => $admin->id,
                'title' => "Posisi {$index}", 'status' => 'active', 'created_at' => now()->subMinutes($index), 'updated_at' => now(),
            ]);
        }
        DB::table('job_posts')->insert([
            'id' => (string) Str::uuid(), 'company_id' => $companyId, 'posted_by' => $admin->id,
            'title' => 'Draf', 'status' => 'inactive', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $first = $this->getJson('/api/job-posts?limit=5&page=1')->assertOk()->assertJsonPath('total', 17)->json('data');
        $second = $this->getJson('/api/job-posts?limit=5&page=2')->assertOk()->assertJsonPath('total', 17)->json('data');
        $last = $this->getJson('/api/job-posts?limit=5&page=4')->assertOk()->assertJsonPath('total', 17)->json('data');
        $this->assertCount(5, $first);
        $this->assertCount(5, $second);
        $this->assertCount(2, $last);
        $this->assertEmpty(array_intersect(array_column($first, 'id'), array_column($second, 'id')));
        $this->getJson('/api/job-posts?limit=0')->assertUnprocessable();
        $this->getJson('/api/job-posts?page=0')->assertUnprocessable();
    }

    public function test_job_filters_combine_multiple_types_with_inclusive_created_date_and_keep_scalar_queries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'name' => 'PT Filter', 'created_at' => now(), 'updated_at' => now()]);
        $firstDay = now()->subDays(2)->startOfDay();
        foreach ([
            ['Analis', 'Bandung', 'Full-time', 'Hybrid', $firstDay->copy()->addHours(23)],
            ['Editor', 'Jakarta', 'Contract', 'Remote', $firstDay->copy()->addDay()->addHours(20)],
            ['Magang', 'Bandung', 'Internship', 'on-site', $firstDay->copy()->addDays(2)],
        ] as [$title, $location, $jobType, $workType, $createdAt]) {
            DB::table('job_posts')->insert([
                'id' => (string) Str::uuid(), 'company_id' => $companyId, 'posted_by' => $admin->id,
                'title' => $title, 'location' => $location, 'job_type' => $jobType,
                'work_type' => $workType, 'status' => 'active', 'created_at' => $createdAt, 'updated_at' => now(),
            ]);
        }

        $query = http_build_query([
            'job_type' => ['Full-time', 'Contract'], 'work_type' => ['Hybrid', 'Remote'],
            'created_from' => $firstDay->toDateString(),
            'created_to' => $firstDay->copy()->addDay()->toDateString(),
            'sort' => 'oldest',
        ]);
        $this->getJson('/api/job-posts?'.$query)->assertOk()->assertJsonPath('total', 2)
            ->assertJsonPath('data.0.title', 'Analis')->assertJsonPath('data.1.title', 'Editor');
        $this->getJson('/api/job-posts?job_type=Full-time&location=Bandung')->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('data.0.title', 'Analis');
        $this->getJson('/api/job-posts?work_type=On-site')->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('data.0.title', 'Magang');
        $this->getJson('/api/job-posts?created_to='.$firstDay->toDateString())->assertOk()
            ->assertJsonPath('total', 1);
        $this->getJson('/api/job-posts?created_from='.$firstDay->copy()->addDay()->toDateString()
            .'&created_to='.$firstDay->toDateString())->assertUnprocessable();
    }

    public function test_company_filters_and_pagination_keep_full_total(): void
    {
        foreach (['PT Bandung Satu', 'PT Bandung Dua', 'PT Bandung Tiga', 'PT Jakarta'] as $name) {
            DB::table('companies')->insert([
                'id' => (string) Str::uuid(), 'name' => $name,
                'location' => str_contains($name, 'Bandung') ? 'Bandung' : 'Jakarta',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->getJson('/api/companies?location=Bandung&limit=2&page=1&sort=a-z')
            ->assertOk()->assertJsonPath('total', 3)->assertJsonCount(2, 'data');
        $this->getJson('/api/companies?location=Bandung&limit=2&page=2&sort=a-z')
            ->assertOk()->assertJsonPath('total', 3)->assertJsonCount(1, 'data');
    }

    public function test_only_owner_can_download_own_private_cv(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'jobseeker']);
        $other = User::factory()->create(['role' => 'jobseeker']);
        $employer = User::factory()->create(['role' => 'employer']);
        $path = 'cvs/'.$owner->id.'/'.Str::uuid().'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 test');
        DB::table('jobseeker_profiles')->insert([
            'id' => (string) Str::uuid(), 'user_id' => $owner->id, 'cv_file' => $path,
            'cv_filename' => 'resume.pdf', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->getJson('/api/jobseeker/profile/cv')->assertUnauthorized();
        $this->actingAs($other)->getJson('/api/jobseeker/profile/cv')->assertNotFound();
        $this->actingAs($employer)->getJson('/api/jobseeker/profile/cv')->assertForbidden();
        $this->actingAs($owner)->get('/api/jobseeker/profile/cv')->assertOk();
    }
}
