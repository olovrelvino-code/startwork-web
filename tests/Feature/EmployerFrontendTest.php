<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmployerFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_search_filters_matching_tables_independently_and_keeps_statistics(): void
    {
        $employer = User::factory()->create(['role' => 'employer', 'full_name' => 'Nina Employer']);
        $other = User::factory()->create(['role' => 'employer']);
        $companyId = (string) Str::uuid();
        $otherCompanyId = (string) Str::uuid();
        DB::table('companies')->insert([
            ['id' => $companyId, 'owner_user_id' => $employer->id, 'name' => 'Studio Kerja', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $otherCompanyId, 'owner_user_id' => $other->id, 'name' => 'Studio Asing', 'created_at' => now(), 'updated_at' => now()],
        ]);
        foreach ([['Rina Pelamar', 'Analis Pelamar', $companyId, $employer->id], ['Sari Pelamar', 'Desainer Produk', $companyId, $employer->id], ['Rahasia Pelamar', 'Lowongan Rahasia', $otherCompanyId, $other->id]] as [$name, $title, $ownerCompanyId, $posterId]) {
            $seeker = User::factory()->create(['role' => 'jobseeker']);
            $jobId = (string) Str::uuid();
            $profileId = (string) Str::uuid();
            DB::table('job_posts')->insert(['id' => $jobId, 'company_id' => $ownerCompanyId, 'posted_by' => $posterId, 'title' => $title, 'status' => 'inactive', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('jobseeker_profiles')->insert(['id' => $profileId, 'user_id' => $seeker->id, 'full_name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('applications')->insert(['id' => (string) Str::uuid(), 'job_posts_id' => $jobId, 'profile_id' => $profileId, 'status' => 'submitted', 'applied_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }

        $byApplicant = $this->actingAs($employer)->get('/employer/dashboard?q=rInA');
        $byApplicant->assertOk()->assertSee('Rina Pelamar')->assertDontSee('Sari Pelamar')->assertDontSee('Rahasia Pelamar')
            ->assertSee('data-upgrade-dialog', false)->assertSee('Sesi Employer')->assertSee('data-upgrade-template', false)
            ->assertSee('Cari nama pelamar atau judul lowongan...');
        $this->assertSame(1, $byApplicant->viewData('applications')->total());
        $this->assertSame(2, $byApplicant->viewData('jobs')->total());
        $this->assertSame(2, $byApplicant->viewData('counts')['applications']);
        $byJob = $this->get('/employer/dashboard?q=dEsAiNeR')->assertOk();
        $this->assertSame(2, $byJob->viewData('applications')->total());
        $this->assertSame(1, $byJob->viewData('jobs')->total());
        $both = $this->get('/employer/dashboard?q=Pelamar')->assertOk();
        $this->assertSame(2, $both->viewData('applications')->total());
        $this->assertSame(1, $both->viewData('jobs')->total());
        $missing = $this->get('/employer/dashboard?q=Rahasia')->assertOk();
        $missing->assertSee('Lamaran tidak ditemukan')->assertSee('Lowongan tidak ditemukan')->assertDontSee('Rahasia Pelamar');
        $this->getJson('/employer/dashboard?q='.str_repeat('x', 256))->assertUnprocessable()->assertJsonValidationErrors('q');
    }

    public function test_job_search_matches_title_or_location_and_preserves_pagination_scope(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $other = User::factory()->create(['role' => 'employer']);
        $companyId = (string) Str::uuid();
        $otherCompanyId = (string) Str::uuid();
        DB::table('companies')->insert([
            ['id' => $companyId, 'owner_user_id' => $employer->id, 'name' => 'Studio Kerja', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $otherCompanyId, 'owner_user_id' => $other->id, 'name' => 'Studio Asing', 'created_at' => now(), 'updated_at' => now()],
        ]);
        foreach (range(1, 17) as $number) {
            DB::table('job_posts')->insert(['id' => (string) Str::uuid(), 'company_id' => $companyId, 'posted_by' => $employer->id, 'title' => sprintf('Analis %02d', $number), 'location' => $number === 17 ? 'Bandung' : 'Jakarta', 'status' => 'inactive', 'created_at' => now()->subSeconds($number), 'updated_at' => now()]);
        }
        DB::table('job_posts')->insert(['id' => (string) Str::uuid(), 'company_id' => $otherCompanyId, 'posted_by' => $other->id, 'title' => 'Analis Asing', 'location' => 'Bandung', 'status' => 'inactive', 'created_at' => now(), 'updated_at' => now()]);

        $first = $this->actingAs($employer)->get('/employer/job-posts?q=ANALIS');
        $first->assertOk()->assertSee('Cari lowongan')->assertDontSee('Analis Asing');
        $this->assertSame(17, $first->viewData('jobs')->total());
        $this->assertCount(15, $first->viewData('jobs'));
        $this->assertStringContainsString('q=ANALIS', $first->viewData('jobs')->nextPageUrl());
        $second = $this->get('/employer/job-posts?q=ANALIS&page=2')->assertOk();
        $this->assertCount(2, $second->viewData('jobs'));
        $this->assertSame(16, $second->viewData('jobs')->firstItem());
        $this->get('/employer/job-posts?q=BANDUNG')->assertOk()->assertSee('Analis 17')->assertDontSee('Analis Asing');
        $this->get('/employer/job-posts?q=TidakAda')->assertOk()->assertSee('Lowongan tidak ditemukan');
        $this->getJson('/employer/job-posts?q='.str_repeat('x', 256))->assertUnprocessable()->assertJsonValidationErrors('q');
    }

    public function test_employer_numbered_pagination_keeps_large_lists_compact_and_resets_on_search(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $companyId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'owner_user_id' => $employer->id,
            'name' => 'Studio Halaman', 'created_at' => now(), 'updated_at' => now()]);
        foreach (range(1, 61) as $number) {
            DB::table('job_posts')->insert(['id' => (string) Str::uuid(), 'company_id' => $companyId,
                'posted_by' => $employer->id, 'title' => sprintf('Posisi %02d', $number),
                'status' => 'draft', 'created_at' => now()->subSeconds($number), 'updated_at' => now()]);
        }

        $middle = $this->actingAs($employer)->get('/employer/dashboard?jobs_page=4');
        $middle->assertOk()->assertSee('Halaman 4 dari 7')
            ->assertSee('aria-label="Halaman 1"', false)
            ->assertSee('aria-label="Halaman 3"', false)
            ->assertSee('aria-current="page" aria-label="Halaman 4"', false)
            ->assertSee('aria-label="Halaman 5"', false)
            ->assertSee('aria-label="Halaman 7"', false);
        $this->assertSame(31, $middle->viewData('jobs')->firstItem());
        $searched = $this->get('/employer/dashboard?q=Posisi%2061')->assertOk()
            ->assertSee('Halaman 1 dari 1')->assertSee('Posisi 61');
        $this->assertSame(1, $searched->viewData('jobs')->firstItem());
    }

    public function test_employer_pages_and_login_are_role_scoped(): void
    {
        $this->get('/')->assertOk()->assertSee('Untuk Perusahaan');
        $this->get('/employer/login')->assertOk()->assertSee('Masuk sebagai Employer')->assertSee('Daftar di sini');
        $this->get('/employer/register')->assertOk()->assertSee('Buat akun dan lanjutkan')
            ->assertSee('Langkah 1 dari 2')->assertSee('Profil perusahaan')
            ->assertSee('registration-progress--dark', false);
        $this->get('/employer/dashboard')->assertRedirect(route('employer.login'));
        $this->get('/employer/setup')->assertRedirect(route('employer.login'));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/employer/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/employer/company')->assertForbidden();

        $jobseeker = User::factory()->create(['role' => 'jobseeker']);
        $this->actingAs($jobseeker)->get('/employer/job-posts')->assertForbidden();

        $employer = User::factory()->create([
            'role' => 'employer', 'password_hash' => Hash::make('example-password'),
        ]);
        $this->post('/logout');
        $this->post('/employer/login', ['email' => $employer->email, 'password' => 'example-password'])
            ->assertRedirect(route('employer.dashboard'));
        $this->get('/employer/dashboard')->assertRedirect(route('employer.setup'));
        $this->get('/employer/setup')->assertOk()->assertSee('Simpan dan lanjutkan')
            ->assertSee('Langkah 2 dari 2');
        $this->get('/employer/company')->assertOk()->assertSee('Informasi perusahaan');
        $this->get('/employer/job-posts/create')->assertRedirect(route('employer.setup'));
    }

    public function test_new_employer_registers_then_sets_up_company_before_dashboard(): void
    {
        $this->postJson('/api/employer/register', [
            'full_name' => 'Employer Baru', 'phone' => '081234567890',
            'email' => 'employer-baru@example.test',
            'password' => 'short', 'password_confirmation' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'employer-baru@example.test']);

        $this->postJson('/api/employer/register', [
            'full_name' => 'Employer Baru', 'phone' => '081234567890',
            'email' => 'employer-baru@example.test',
            'password' => 'example-password', 'password_confirmation' => 'example-password',
        ])->assertCreated()->assertJsonPath('data.role', 'employer');

        $this->get('/employer/register')->assertRedirect(route('employer.dashboard'));
        $this->get('/employer/dashboard')->assertRedirect(route('employer.setup'));
        $this->get('/employer/setup')->assertOk()->assertSee('Langkah 2 dari 2')
            ->assertSee('aria-current="step"', false)
            ->assertSee('data-state="complete"', false)
            ->assertSee('registration-progress--dark', false)
            ->assertSee('data-success-url="'.route('employer.dashboard').'"', false);
        $this->get('/employer/setup')->assertOk()->assertSee('Nama perusahaan');
        $this->assertSame(1, DB::table('users')->where('email', 'employer-baru@example.test')->count());

        $this->postJson('/api/employer/company', ['name' => 'Studio Kerja'])
            ->assertUnprocessable()->assertJsonValidationErrors('location');
        $this->get('/employer/dashboard')->assertRedirect(route('employer.setup'));
        $this->assertSame(0, DB::table('companies')->where('owner_user_id', auth()->id())->count());

        $companyId = $this->postJson('/api/employer/company', [
            'name' => 'Studio Kerja', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');

        $this->get('/employer/setup')->assertRedirect(route('employer.dashboard'));
        $this->get('/employer/dashboard')->assertOk()->assertSee('Studio Kerja');
        $this->get('/employer/company')->assertOk()->assertSee('Logo perusahaan');
        $this->postJson('/api/employer/company', ['name' => 'Perusahaan Duplikat', 'location' => 'Bandung'])
            ->assertStatus(409);
        $this->assertSame(1, DB::table('companies')->where('owner_user_id', auth()->id())->count());
        $this->assertDatabaseHas('companies', ['id' => $companyId, 'owner_user_id' => auth()->id()]);
    }

    public function test_employer_pages_only_show_own_company_jobs_and_applications(): void
    {
        $owner = User::factory()->create(['role' => 'employer']);
        $other = User::factory()->create(['role' => 'employer']);
        $companyId = $this->actingAs($owner)->postJson('/api/employer/company', [
            'name' => 'Studio Kerja', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');
        $jobId = $this->postJson('/api/employer/job-posts', [
            'title' => 'Analis Data', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid',
        ])->assertCreated()->json('data.id');
        $this->get('/employer/job-posts/'.$jobId)->assertOk()
            ->assertSee('Lowongan belum diterbitkan')->assertDontSee('Terbitkan lowongan');
        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $profileId = (string) Str::uuid();
        $applicationId = (string) Str::uuid();
        Storage::fake('local');
        Storage::disk('local')->put('cvs/test.pdf', '%PDF-1.4 test');
        DB::table('jobseeker_profiles')->insert([
            'id' => $profileId, 'user_id' => $seeker->id, 'full_name' => 'Siti Pelamar',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('applications')->insert([
            'id' => $applicationId, 'job_posts_id' => $jobId, 'profile_id' => $profileId,
            'status' => 'submitted', 'cv_file' => 'cvs/test.pdf',
            'applied_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get('/employer/dashboard')->assertOk()->assertSee('Studio Kerja')->assertSee('Siti Pelamar');
        $this->get('/employer/job-posts')->assertOk()->assertSee('Analis Data');
        $this->get('/employer/job-posts/'.$jobId.'/edit')->assertOk()->assertSee('Simpan perubahan');
        $this->get('/employer/applications/'.$applicationId)->assertOk()
            ->assertSee('Siti Pelamar')->assertSee('data-employer-download', false);
        $this->get('/api/employer/applications/'.$applicationId.'/cv')->assertOk();
        $this->putJson('/api/employer/applications/'.$applicationId.'/status', ['status' => 'in_process'])
            ->assertOk()->assertJsonPath('data.status', 'in_process');
        $this->get('/employer/applications/'.$applicationId)->assertOk()->assertSee('Terima')->assertSee('Tolak');
        $this->putJson('/api/employer/applications/'.$applicationId.'/status', ['status' => 'accepted'])
            ->assertOk()->assertJsonPath('data.status', 'accepted');
        $this->putJson('/api/employer/applications/'.$applicationId.'/status', ['status' => 'rejected'])
            ->assertStatus(409);
        $this->get('/employer/applications/'.$applicationId)->assertOk()->assertSee('Status akhir sudah tersimpan');
        $this->assertDatabaseHas('companies', ['id' => $companyId, 'owner_user_id' => $owner->id]);

        $this->actingAs($other)->get('/employer/job-posts/'.$jobId)->assertNotFound();
        $this->get('/employer/job-posts/'.$jobId.'/edit')->assertNotFound();
        $this->get('/employer/applications/'.$applicationId)->assertNotFound();
        $this->get('/api/employer/applications/'.$applicationId.'/cv')->assertNotFound();
        $this->putJson('/api/employer/applications/'.$applicationId.'/status', ['status' => 'rejected'])
            ->assertNotFound();
        $this->get('/employer/job-posts')->assertOk()->assertDontSee('Analis Data');
        $this->get('/employer/applications')->assertOk()->assertDontSee('Siti Pelamar');
    }

    public function test_employer_applications_api_paginates_with_total_and_scope(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $companyId = $this->actingAs($employer)->postJson('/api/employer/company', [
            'name' => 'Studio Kerja', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');
        $jobId = $this->postJson('/api/employer/job-posts', [
            'title' => 'Analis Data', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid',
        ])->assertCreated()->json('data.id');

        foreach (['Pelamar Lama', 'Pelamar Baru'] as $index => $name) {
            $seeker = User::factory()->create(['role' => 'jobseeker']);
            $profileId = (string) Str::uuid();
            DB::table('jobseeker_profiles')->insert([
                'id' => $profileId, 'user_id' => $seeker->id, 'full_name' => $name,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('applications')->insert([
                'id' => (string) Str::uuid(), 'job_posts_id' => $jobId, 'profile_id' => $profileId,
                'status' => 'submitted', 'applied_at' => now()->addMinutes($index),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->getJson('/api/employer/applications?job_id='.$jobId.'&limit=1&page=2')
            ->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.applicant_name', 'Pelamar Lama');
        $this->getJson('/api/employer/applications?limit=51')
            ->assertUnprocessable()->assertJsonValidationErrors('limit');
        $this->assertDatabaseHas('companies', ['id' => $companyId]);
    }

    public function test_employer_job_expiry_round_trips_between_wib_and_utc(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $this->actingAs($employer)->postJson('/api/employer/company', [
            'name' => 'Studio Kerja', 'location' => 'Jakarta',
        ])->assertCreated();
        $jobId = $this->postJson('/api/employer/job-posts', [
            'title' => 'Analis Data', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid',
            'expires_at' => '2030-01-01T17:00+07:00',
        ])->assertCreated()->assertJsonPath('data.expires_at', '2030-01-01 10:00:00')->json('data.id');

        $this->get('/employer/job-posts/'.$jobId.'/edit')
            ->assertOk()->assertSee('value="2030-01-01T17:00"', false);
        $this->putJson('/api/employer/job-posts/'.$jobId, [
            'expires_at' => '2030-01-02T09:30+07:00',
        ])->assertOk()->assertJsonPath('data.expires_at', '2030-01-02 02:30:00');
    }
}
