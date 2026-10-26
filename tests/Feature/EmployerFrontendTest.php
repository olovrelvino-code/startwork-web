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

    public function test_employer_pages_and_login_are_role_scoped(): void
    {
        $this->get('/')->assertOk()->assertSee('Untuk Perusahaan');
        $this->get('/employer/login')->assertOk()->assertSee('Masuk sebagai Employer')->assertSee('Daftar di sini');
        $this->get('/employer/register')->assertOk()->assertSee('Buat akun Employer');
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
        $this->get('/employer/setup')->assertOk()->assertSee('Simpan & lanjutkan');
        $this->get('/employer/company')->assertOk()->assertSee('Informasi perusahaan');
        $this->get('/employer/job-posts/create')->assertRedirect(route('employer.setup'));
    }

    public function test_new_employer_registers_then_sets_up_company_before_dashboard(): void
    {
        $this->postJson('/api/employer/register', [
            'full_name' => 'Employer Baru', 'phone' => '081234567890',
            'email' => 'employer-baru@example.test',
            'password' => 'example-password', 'password_confirmation' => 'example-password',
        ])->assertCreated()->assertJsonPath('data.role', 'employer');

        $this->get('/employer/dashboard')->assertRedirect(route('employer.setup'));
        $this->get('/employer/setup')->assertOk()->assertSee('Nama perusahaan');

        $companyId = $this->postJson('/api/employer/company', [
            'name' => 'Studio Kerja', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');

        $this->get('/employer/setup')->assertOk()->assertSee('Logo perusahaan')
            ->assertSee('Lanjut ke dashboard');
        $this->get('/employer/dashboard')->assertOk()->assertSee('Studio Kerja');
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
            ->assertSee('Lowongan tersimpan sebagai draft')->assertDontSee('Coba aktifkan');
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
