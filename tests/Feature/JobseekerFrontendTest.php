<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class JobseekerFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_sections_use_only_active_jobs_and_keep_demo_content_labeled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'name' => 'PT Karier Nyata', 'location' => 'Bandung', 'logo' => 'logo.webp', 'created_at' => now(), 'updated_at' => now()]);
        foreach (['active', 'inactive'] as $status) {
            DB::table('job_posts')->insert([
                'id' => (string) Str::uuid(), 'company_id' => $companyId, 'posted_by' => $admin->id,
                'title' => "Posisi {$status}", 'description' => 'Kebutuhan khusus', 'location' => 'Bandung', 'category' => 'Technology / IT',
                'job_type' => $status === 'active' ? 'Full-time' : 'Contract',
                'work_type' => $status === 'active' ? 'Remote' : 'On-site', 'status' => $status,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $response = $this->get('/')->assertOk()->assertSee('PT Karier Nyata')->assertSee('Technology / IT')
            ->assertSee('89,95%')->assertSee('Peluang Menuju Karier Impianmu!')
            ->assertSee('Temukan pekerjaan yang tepat, kenali perusahaan pilihanmu, dan mulai perjalanan kariermu bersama StartWork.')
            ->assertDontSee('konten promosi sementara')
            ->assertSee('Posisi active')->assertDontSee('Posisi inactive')
            ->assertSee(url('/api/companies/'.$companyId.'/logo'))
            ->assertSee('Data demo')->assertSee('Mitra Perusahaan')->assertSee('Logo dan daftar mitra resmi belum tersedia.')
            ->assertDontSee('Perusahaan paling aktif')
            ->assertSee('Nama lowongan')->assertSee('Kota atau lokasi')
            ->assertSee('action="'.route('jobs.index').'"', false)
            ->assertSee('name="q"', false)->assertSee('name="location"', false)
            ->assertSee('Cari')
            ->assertSeeInOrder(['Posisi active', 'Bandung', 'Technology / IT', 'Full-time', 'Remote'])
            ->assertDontSee('Contract')->assertDontSee('On-site')
            ->assertSee('Registrasi Perusahaan')
            ->assertSee(route('employer.register'))
            ->assertSee(route('login'))->assertSee(route('register'))
            ->assertDontSee('Log Out')
            ->assertDontSee('Asisten AI')->assertDontSee('Kategori lowongan aktif')
            ->assertDontSee('Perusahaan terdaftar');
        $this->assertSame(1, substr_count($response->getContent(), url('/api/companies/'.$companyId.'/logo')));
        $this->getJson('/api/job-posts?category='.urlencode('Technology / IT'))
            ->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/job-posts?category[]=Technology%20%2F%20IT&category[]=Others')
            ->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/job-posts?q=Posisi')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/job-posts?q=Kebutuhan')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/job-posts?q=Posisi&location=Bandung')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/job-posts?q=Posisi&location=Jakarta')->assertOk()->assertJsonPath('total', 0);
    }

    public function test_job_listing_banner_precedes_filters(): void
    {
        $this->get(route('jobs.index'))->assertOk()
            ->assertSee('Peluangmu Dimulai di Sini.')
            ->assertSee('Temukan pekerjaan yang tepat dan ambil langkah pertama menuju karier impian bersama StartWork.')
            ->assertSeeInOrder(['Peluangmu Dimulai di Sini.', 'Nama Lowongan', 'Urutkan', 'Filter lowongan', 'data-list-status']);
    }

    public function test_login_shows_jobseeker_by_default_and_exposes_employer_switch(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('data-auth-role="jobseeker" aria-pressed="true"', false)
            ->assertSee('data-auth-role="employer" aria-pressed="false"', false)
            ->assertSee('data-jobseeker-auth', false)
            ->assertSee('data-employer-inline-content', false);
    }

    public function test_homepage_latest_articles_match_published_blog_and_precede_testimonials(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach ([['published', 'Panduan Lamaran', 'panduan-lamaran'], ['draft', 'Catatan Rahasia', 'catatan-rahasia']] as [$status, $title, $slug]) {
            DB::table('articles')->insert([
                'id' => (string) Str::uuid(), 'author_id' => $admin->id, 'title' => $title,
                'slug' => $slug, 'excerpt' => 'Ringkasan karier', 'content' => 'Isi artikel',
                'status' => $status, 'published_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->get('/')->assertOk()
            ->assertSee('Panduan Lamaran')->assertSee('Ringkasan karier')
            ->assertSee(route('articles.show', 'panduan-lamaran'))
            ->assertDontSee('Catatan Rahasia')
            ->assertSeeInOrder(['Lowongan terbaru', 'Mitra Perusahaan', 'Artikel Terbaru', 'Cerita pengguna']);
        $this->get('/articles')->assertOk()->assertSee('Panduan Lamaran')->assertDontSee('Catatan Rahasia');
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
        $this->get('/')->assertOk()->assertSee('Dashboard')->assertDontSee('data-nav-photo', false);

        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $this->actingAs($seeker)->get('/profil')->assertOk()->assertSee('Profil Saya');
        $this->get('/profil/lamaran')->assertOk()->assertSee('Lamaran Saya');
        $this->get('/loker')->assertSee('aria-current="page"', false)
            ->assertSee(route('account.switch', ['role' => 'employer']));
    }

    public function test_job_listing_omits_date_controls_and_profile_places_photo_after_readiness(): void
    {
        $this->get('/loker')->assertOk()
            ->assertDontSee('name="created_from"', false)
            ->assertDontSee('name="created_to"', false)
            ->assertSee('name="sort"', false)
            ->assertSee('name="location"', false)
            ->assertSee('Kategori');

        $this->getJson('/api/job-posts?category[]=Technology%20%2F%20IT')->assertOk();

        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $this->actingAs($seeker)->get('/profil')->assertOk()
            ->assertSeeInOrder(['id="readiness-title"', 'data-upload="photo"', 'id="identity-title"'], false)
            ->assertSee('data-ready-icon="profile"', false)
            ->assertSee('data-ready-icon="skills"', false)
            ->assertSee('data-ready-icon="cv"', false);
    }

    public function test_job_categories_show_one_indonesian_technology_option_and_keep_multiple_selections(): void
    {
        $response = $this->get('/loker?category[]=Teknologi&category[]=Keuangan%20%26%20Audit');

        $response->assertOk()
            ->assertSee('value="Teknologi" data-query-values="Technology / IT|technology|teknologi" checked', false)
            ->assertSee('value="Keuangan &amp; Audit" data-query-values="Finance &amp; Audit" checked', false)
            ->assertDontSee('Show More');
        $this->assertSame(13, substr_count($response->getContent(), 'name="category[]"'));
        $this->assertSame(1, substr_count($response->getContent(), 'value="Teknologi"'));
    }

    public function test_jobseeker_nav_uses_own_private_photo_and_profile_name(): void
    {
        Storage::fake('local');
        $seeker = User::factory()->create(['role' => 'jobseeker', 'name' => 'Nama Akun']);
        $this->actingAs($seeker)->postJson('/api/jobseeker/profile', [
            'full_name' => 'Nama Profil', 'domicile' => 'Bandung', 'birth_date' => '2000-01-01',
            'gender' => 'female', 'last_education' => 'S1',
        ])->assertCreated();
        $this->get('/loker')->assertOk()->assertSee('data-nav-photo-fallback', false)
            ->assertDontSee('src="'.url('/api/jobseeker/profile/photo').'"', false);
        $this->post('/api/jobseeker/profile/photo', [
            'photo' => UploadedFile::fake()->image('avatar.png'),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->get('/loker')->assertOk()->assertSee('Nama Profil')
            ->assertSee('data-nav-photo', false)
            ->assertSee(url('/api/jobseeker/profile/photo'))
            ->assertSee('Profil Saya')->assertSee('Lamaran Saya')->assertSee('Log Out')
            ->assertSee('action="'.route('logout').'"', false);
        $this->get('/api/jobseeker/profile/photo')->assertOk()->assertHeader('Content-Type', 'image/webp');
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
        DB::table('companies')->insert(['id' => $companyId, 'name' => 'PT Uji', 'logo' => 'logo.webp', 'created_at' => now(), 'updated_at' => now()]);
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

        $first = $this->getJson('/api/job-posts?limit=5&page=1')->assertOk()->assertJsonPath('total', 17)
            ->assertJsonPath('data.0.company_id', $companyId)->assertJsonPath('data.0.company_logo', 'logo.webp')->json('data');
        $second = $this->getJson('/api/job-posts?limit=5&page=2')->assertOk()->assertJsonPath('total', 17)->json('data');
        $last = $this->getJson('/api/job-posts?limit=5&page=4')->assertOk()->assertJsonPath('total', 17)->json('data');
        $this->assertCount(5, $first);
        $this->assertCount(5, $second);
        $this->assertCount(2, $last);
        $this->assertEmpty(array_intersect(array_column($first, 'id'), array_column($second, 'id')));
        $this->getJson('/api/job-posts?limit=0')->assertUnprocessable();
        $this->getJson('/api/job-posts?page=0')->assertUnprocessable();
        $this->get('/loker')->assertOk()->assertSee('data-field="logo-fallback"', false);
    }

    public function test_job_filters_show_dynamic_job_types_and_predefined_work_types(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'name' => 'PT Filter', 'created_at' => now(), 'updated_at' => now()]);
        foreach ([['Seasonal', 'Field', 'active'], ['Contract', 'Remote', 'inactive']] as [$jobType, $workType, $status]) {
            DB::table('job_posts')->insert([
                'id' => (string) Str::uuid(), 'company_id' => $companyId, 'posted_by' => $admin->id,
                'title' => $jobType, 'job_type' => $jobType, 'work_type' => $workType,
                'status' => $status, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->get('/loker')->assertOk()
            ->assertSee('value="Seasonal"', false)->assertDontSee('value="Contract"', false)
            ->assertSee('value="On-site"', false)->assertSee('value="Hybrid"', false)
            ->assertSee('value="Remote"', false)->assertSee('value="Work From Home"', false)
            ->assertDontSee('value="Field"', false);
        $this->getJson('/api/job-posts?job_type%5B%5D=Seasonal')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_my_applications_can_be_paged_and_filtered_without_exposing_another_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $other = User::factory()->create(['role' => 'jobseeker']);
        $companyId = (string) Str::uuid();
        $profileId = (string) Str::uuid();
        $otherProfileId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'name' => 'PT Lamaran', 'created_at' => now(), 'updated_at' => now()]);
        foreach ([[$profileId, $seeker->id], [$otherProfileId, $other->id]] as [$id, $userId]) {
            DB::table('jobseeker_profiles')->insert([
                'id' => $id, 'user_id' => $userId, 'full_name' => 'Pelamar',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        for ($index = 0; $index < 15; $index++) {
            $jobId = (string) Str::uuid();
            DB::table('job_posts')->insert([
                'id' => $jobId, 'company_id' => $companyId, 'posted_by' => $admin->id,
                'title' => "Posisi {$index}", 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('applications')->insert([
                'id' => (string) Str::uuid(), 'job_posts_id' => $jobId, 'profile_id' => $profileId,
                'applied_at' => now()->subMinutes($index), 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($index === 0) {
                DB::table('applications')->insert([
                    'id' => (string) Str::uuid(), 'job_posts_id' => $jobId, 'profile_id' => $otherProfileId,
                    'applied_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                $filteredJobId = $jobId;
            }
        }

        $this->getJson('/api/jobseeker/my-applications?page=1&limit=12')->assertUnauthorized();
        $this->actingAs($admin)->getJson('/api/jobseeker/my-applications?page=1&limit=12')->assertForbidden();
        $this->actingAs($seeker)->getJson('/api/jobseeker/my-applications?page=1&limit=12')
            ->assertOk()->assertJsonPath('total', 15)->assertJsonCount(12, 'data');
        $this->getJson('/api/jobseeker/my-applications?page=2&limit=12')
            ->assertOk()->assertJsonPath('total', 15)->assertJsonCount(3, 'data');
        $this->getJson('/api/jobseeker/my-applications?job_id='.$filteredJobId.'&limit=1')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.job_id', $filteredJobId);
        $this->getJson('/api/jobseeker/my-applications')->assertOk()->assertJsonCount(15, 'data');
        $this->getJson('/api/jobseeker/my-applications?page=0')->assertUnprocessable();
        $this->getJson('/api/jobseeker/my-applications?limit=51')->assertUnprocessable();
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

    public function test_profile_photo_is_converted_to_private_webp_without_losing_old_photo_on_failure(): void
    {
        Storage::fake('local');
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $profileId = (string) Str::uuid();
        $oldName = Str::uuid().'.jpeg';
        $oldPath = "profile-photos/{$seeker->id}/{$oldName}";
        Storage::disk('local')->put($oldPath, 'legacy photo');
        DB::table('jobseeker_profiles')->insert([
            'id' => $profileId, 'user_id' => $seeker->id, 'profile_photo' => $oldName,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($seeker);

        $this->post('/api/jobseeker/profile/photo', ['photo' => UploadedFile::fake()->image('large.png')->size(2049)], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->post('/api/jobseeker/profile/photo', ['photo' => UploadedFile::fake()->createWithContent('fake.jpg', 'invalid')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('photo');
        Storage::disk('local')->assertExists($oldPath);
        $this->assertSame($oldName, DB::table('jobseeker_profiles')->where('id', $profileId)->value('profile_photo'));

        foreach (['jpg', 'jpeg', 'png'] as $extension) {
            $filename = $this->post('/api/jobseeker/profile/photo', [
                'photo' => UploadedFile::fake()->image("new.{$extension}")->size(2048),
            ], ['Accept' => 'application/json'])->assertOk()->json('filename');
            $this->assertStringEndsWith('.webp', $filename);
            $path = "profile-photos/{$seeker->id}/{$filename}";
            Storage::disk('local')->assertExists($path);
            $this->assertSame('image/webp', getimagesizefromstring(Storage::disk('local')->get($path))['mime']);
        }
        $wideName = $this->post('/api/jobseeker/profile/photo', [
            'photo' => UploadedFile::fake()->image('wide.png', 1600, 900),
        ], ['Accept' => 'application/json'])->assertOk()->json('filename');
        $dimensions = getimagesizefromstring(Storage::disk('local')->get("profile-photos/{$seeker->id}/{$wideName}"));
        $this->assertSame([1024, 576], [$dimensions[0], $dimensions[1]]);
        Storage::disk('local')->assertMissing($oldPath);
        $this->get('/api/jobseeker/profile/photo')->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    public function test_saved_jobs_are_persistent_idempotent_and_private_to_each_jobseeker(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $first = User::factory()->create(['role' => 'jobseeker']);
        $second = User::factory()->create(['role' => 'jobseeker']);
        $companyId = (string) Str::uuid();
        $jobId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'name' => 'Perusahaan Uji', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('job_posts')->insert([
            'id' => $jobId, 'company_id' => $companyId, 'posted_by' => $admin->id,
            'title' => 'Posisi Tersimpan', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->postJson("/api/jobseeker/saved-jobs/{$jobId}")->assertUnauthorized();
        $this->actingAs($admin)->postJson("/api/jobseeker/saved-jobs/{$jobId}")->assertForbidden();
        $this->actingAs($first)->postJson("/api/jobseeker/saved-jobs/{$jobId}")->assertOk();
        $this->postJson("/api/jobseeker/saved-jobs/{$jobId}")->assertOk();
        $this->assertSame(1, DB::table('saved_job_posts')->count());
        $this->getJson('/api/jobseeker/saved-jobs')->assertOk()->assertJsonPath('data.0', $jobId);
        $this->get('/profil/tersimpan')->assertOk()->assertSee('Posisi Tersimpan');

        $this->actingAs($second)->getJson('/api/jobseeker/saved-jobs')->assertOk()->assertJsonCount(0, 'data');
        $this->get('/profil/tersimpan')->assertOk()->assertDontSee('Posisi Tersimpan');
        $this->deleteJson("/api/jobseeker/saved-jobs/{$jobId}")->assertOk();
        $this->assertSame(1, DB::table('saved_job_posts')->count());
        $this->actingAs($first)->deleteJson("/api/jobseeker/saved-jobs/{$jobId}")->assertOk();
        $this->assertSame(0, DB::table('saved_job_posts')->count());
    }

    public function test_profile_education_gender_and_icon_controls_keep_legacy_values_editable(): void
    {
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $page = $this->actingAs($seeker)->get('/profil')->assertOk()
            ->assertSee('name="gender" value="Laki-laki"', false)
            ->assertSee('name="gender" value="Perempuan"', false)
            ->assertSee('value="D4/Sarjana Terapan"', false)
            ->assertSee('value="SD/Sederajat"', false)
            ->assertSee('aria-label="Tambah skills"', false)
            ->assertSee('aria-label="Hapus riwayat pendidikan"', false)
            ->assertDontSee('>Hapus entri</button>', false)
            ->assertDontSee('>Profesi</option>', false);
        $this->assertSame(1, substr_count($page->getContent(), 'value="SD/Sederajat"'));

        $this->postJson('/api/jobseeker/profile', [
            'full_name' => 'Rina', 'domicile' => 'Bandung', 'birth_date' => '2000-01-01',
            'gender' => 'Laki-laki', 'last_education' => 'S1 Teknik Informatika',
        ])->assertCreated();
        $this->getJson('/api/jobseeker/profile')->assertOk()
            ->assertJsonPath('data.gender', 'Laki-laki')
            ->assertJsonPath('data.last_education', 'S1 Teknik Informatika');
        $this->postJson('/api/jobseeker/profile', [
            'full_name' => 'Rina', 'domicile' => 'Bandung', 'birth_date' => '2000-01-01',
            'gender' => 'Perempuan', 'last_education' => 'D4/Sarjana Terapan',
        ])->assertOk()->assertJsonPath('data.gender', 'Perempuan')->assertJsonPath('data.last_education', 'D4/Sarjana Terapan');
        $this->postJson('/api/jobseeker/profile/details', [
            'skills' => ['SQL'], 'education_histories' => [['level' => 'S1', 'institution' => 'Universitas A']],
        ])->assertOk();
        $this->putJson('/api/jobseeker/profile/details', [
            'skills' => ['SQL'], 'education_histories' => [['level' => 'SMK/Sederajat', 'institution' => 'Sekolah B']],
        ])->assertOk();
        $this->getJson('/api/jobseeker/profile/details')->assertOk()
            ->assertJsonPath('data.education_histories.0.level', 'SMK/Sederajat')
            ->assertJsonPath('data.education_histories.0.institution', 'Sekolah B');
    }

    public function test_cv_upload_validates_documents_and_preserves_application_copies(): void
    {
        Storage::fake('local');
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $other = User::factory()->create(['role' => 'jobseeker']);
        $profileId = (string) Str::uuid();
        DB::table('jobseeker_profiles')->insert(['id' => $profileId, 'user_id' => $seeker->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($seeker);

        foreach (['pdf', 'jpg', 'jpeg', 'png'] as $extension) {
            $file = $extension === 'pdf'
                ? UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\n".str_repeat(' ', 2 * 1024 * 1024)."\n%%EOF\n")
                : UploadedFile::fake()->image("resume.{$extension}")->size($extension === 'jpg' ? 2048 : 100);
            $this->post('/api/jobseeker/profile/cv', ['cv' => $file], ['Accept' => 'application/json'])->assertOk();
            $path = DB::table('jobseeker_profiles')->where('id', $profileId)->value('cv_file');
            Storage::disk('local')->assertExists($path);
            $this->get('/api/jobseeker/profile/cv')->assertOk();
            $preview = $this->get('/api/jobseeker/profile/cv?preview=1')->assertOk();
            $this->assertStringContainsString('inline', $preview->headers->get('Content-Disposition'));
        }

        $oldPath = $path;
        $this->post('/api/jobseeker/profile/cv', ['cv' => UploadedFile::fake()->image('large.png')->size(2049)], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('cv');
        $this->post('/api/jobseeker/profile/cv', ['cv' => UploadedFile::fake()->createWithContent('fake.pdf', 'invalid')], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('cv');
        $this->post('/api/jobseeker/profile/cv', ['cv' => UploadedFile::fake()->createWithContent('wrong.txt', "%PDF-1.4\n%%EOF")], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('cv');
        $this->assertSame($oldPath, DB::table('jobseeker_profiles')->where('id', $profileId)->value('cv_file'));
        Storage::disk('local')->assertExists($oldPath);
        $this->actingAs($other)->get('/api/jobseeker/profile/cv')->assertNotFound();
        $this->delete('/api/jobseeker/profile/cv')->assertNotFound();
        $this->actingAs($seeker);
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = (string) Str::uuid();
        $jobId = (string) Str::uuid();
        $applicationId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'owner_user_id' => $employer->id, 'name' => 'PT Contoh']);
        DB::table('job_posts')->insert(['id' => $jobId, 'company_id' => $companyId, 'posted_by' => $employer->id, 'title' => 'Analis', 'status' => 'active']);
        DB::table('applications')->insert(['id' => $applicationId, 'job_posts_id' => $jobId, 'profile_id' => $profileId, 'cv_file' => $oldPath]);
        $this->post('/api/jobseeker/profile/cv', ['cv' => UploadedFile::fake()->image('replacement.jpg')], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('local')->assertExists($oldPath);
        $newPath = DB::table('jobseeker_profiles')->where('id', $profileId)->value('cv_file');
        $this->delete('/api/jobseeker/profile/cv')->assertNoContent();
        Storage::disk('local')->assertMissing($newPath);
        Storage::disk('local')->assertExists($oldPath);
        $this->get('/api/jobseeker/profile/cv')->assertNotFound();
        $employerCv = $this->actingAs($employer)->get("/api/employer/applications/{$applicationId}/cv")->assertOk();
        $this->assertStringContainsString('CV.png', $employerCv->headers->get('Content-Disposition'));
        $adminCv = $this->actingAs($admin)->get("/api/admin/applicants/{$applicationId}/cv")->assertOk();
        $this->assertStringContainsString('CV.png', $adminCv->headers->get('Content-Disposition'));
    }

    public function test_certificate_upload_replacement_deletion_and_legacy_links_are_private(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'jobseeker']);
        $other = User::factory()->create(['role' => 'jobseeker']);
        $profileId = (string) Str::uuid();
        DB::table('jobseeker_profiles')->insert(['id' => $profileId, 'user_id' => $owner->id, 'created_at' => now(), 'updated_at' => now()]);
        $legacyId = (string) Str::uuid();
        DB::table('certificates')->insert(['id' => $legacyId, 'profile_id' => $profileId, 'name' => 'Sertifikat Lama', 'file_url' => 'https://example.test/legacy']);
        $this->assertTrue(Schema::hasColumn('certificates', 'file_path'));
        $this->actingAs($owner)->getJson('/api/jobseeker/profile/details')->assertOk()
            ->assertJsonPath('data.certificates.0.file_url', 'https://example.test/legacy');
        $this->postJson('/api/jobseeker/profile/details', ['skills' => ['SQL']])->assertOk();
        $this->assertDatabaseHas('certificates', ['id' => $legacyId, 'file_url' => 'https://example.test/legacy']);

        $pdf = UploadedFile::fake()->createWithContent('certificate.pdf', "%PDF-1.4\n".str_repeat(' ', 2 * 1024 * 1024)."\n%%EOF\n");
        $this->post('/api/jobseeker/profile/certificates', ['name' => 'Sertifikat Baru', 'file' => $pdf], ['Accept' => 'application/json'])->assertOk();
        $certificate = DB::table('certificates')->where('name', 'Sertifikat Baru')->first();
        $this->assertNull($certificate->file_url);
        Storage::disk('local')->assertExists($certificate->file_path);
        foreach (['jpg', 'jpeg'] as $extension) {
            $this->post('/api/jobseeker/profile/certificates', [
                'name' => "Sertifikat {$extension}", 'file' => UploadedFile::fake()->image("certificate.{$extension}"),
            ], ['Accept' => 'application/json'])->assertOk();
            $otherCertificate = DB::table('certificates')->where('name', "Sertifikat {$extension}")->first();
            Storage::disk('local')->assertExists($otherCertificate->file_path);
            $this->delete("/api/jobseeker/profile/certificates/{$otherCertificate->id}")->assertNoContent();
        }
        $this->get("/api/jobseeker/profile/certificates/{$certificate->id}/file")->assertOk();
        $preview = $this->get("/api/jobseeker/profile/certificates/{$certificate->id}/file?preview=1")->assertOk();
        $this->assertStringContainsString('inline', $preview->headers->get('Content-Disposition'));
        $this->postJson('/api/jobseeker/profile/details', ['skills' => ['SQL'], 'certificates' => [['name' => 'Tertimpa']]])->assertStatus(409);
        Storage::disk('local')->assertExists($certificate->file_path);
        $this->actingAs($other)->get("/api/jobseeker/profile/certificates/{$certificate->id}/file")->assertNotFound();
        $this->delete("/api/jobseeker/profile/certificates/{$certificate->id}")->assertNotFound();
        $this->actingAs($owner);

        $this->post("/api/jobseeker/profile/certificates/{$certificate->id}", [
            'name' => 'Sertifikat Baru', 'file' => UploadedFile::fake()->image('large.jpg')->size(2049),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->post("/api/jobseeker/profile/certificates/{$certificate->id}", [
            'name' => 'Sertifikat Baru', 'file' => UploadedFile::fake()->createWithContent('fake.png', 'invalid'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
        Storage::disk('local')->assertExists($certificate->file_path);
        $this->post("/api/jobseeker/profile/certificates/{$certificate->id}", [
            'name' => 'Sertifikat Baru', 'file' => UploadedFile::fake()->image('replacement.png'),
        ], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('local')->assertMissing($certificate->file_path);
        $replacement = DB::table('certificates')->where('id', $certificate->id)->first();
        Storage::disk('local')->assertExists($replacement->file_path);
        $this->post("/api/jobseeker/profile/certificates/{$certificate->id}", ['name' => 'Sertifikat Diedit'], ['Accept' => 'application/json'])->assertOk();
        $this->assertDatabaseHas('certificates', ['id' => $certificate->id, 'name' => 'Sertifikat Diedit', 'file_path' => $replacement->file_path]);
        $this->delete("/api/jobseeker/profile/certificates/{$certificate->id}")->assertNoContent();
        Storage::disk('local')->assertMissing($replacement->file_path);
        $this->assertDatabaseHas('certificates', ['id' => $legacyId, 'file_url' => 'https://example.test/legacy']);
    }
}
