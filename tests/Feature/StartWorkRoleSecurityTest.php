<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StartWorkRoleSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_owns_multiple_companies_and_employer_cannot_claim_them(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);

        $first = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'Formulatrix', 'location' => 'Semarang',
        ])->assertCreated()->assertJsonPath('data.owner_user_id', $admin->id)->json('data.id');
        $second = $this->postJson('/api/admin/companies', [
            'name' => 'Perusahaan Kedua', 'location' => 'Jakarta',
        ])->assertCreated()->assertJsonPath('data.owner_user_id', $admin->id)->json('data.id');

        $this->actingAs($employer)->postJson('/api/employer/company', [
            'name' => 'Formulatrix', 'location' => 'Semarang',
        ])->assertCreated();
        $this->getJson('/api/employer/job-posts')->assertOk()->assertJsonPath('total', 0);
        $this->actingAs($admin)->postJson("/api/admin/companies/{$first}/assign-employer", [
            'employer_user_id' => $employer->id,
        ])->assertStatus(410);

        $this->assertDatabaseHas('companies', ['id' => $first, 'owner_user_id' => $admin->id]);
        $this->assertDatabaseHas('companies', ['id' => $second, 'owner_user_id' => $admin->id]);
    }

    public function test_public_write_routes_and_cross_role_edits_are_closed(): void
    {
        $this->postJson('/api/companies', ['owner_user_id' => 'spoof'])->assertStatus(410);
        $this->deleteJson('/api/job-posts/fake')->assertStatus(410);
        $this->postJson('/api/admin/articles', ['author_id' => 'spoof'])->assertUnauthorized();
        $this->postJson('/api/jobseeker/profile', ['user_id' => 'spoof'])->assertUnauthorized();
        $this->postJson('/api/job-posts/fake/apply', ['user_id' => 'spoof'])->assertUnauthorized();
        $this->getJson('/api/employer/applicants/fake')->assertStatus(410);

        $employer = User::factory()->create(['role' => 'employer']);
        $this->actingAs($employer)->postJson('/api/admin/companies', [
            'name' => 'Blocked', 'location' => 'Bogor',
        ])->assertForbidden();
    }

    public function test_apply_requires_own_complete_profile_skills_and_uploaded_cv(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'Example', 'location' => 'Semarang',
        ])->assertCreated()->json('data.id');
        $jobId = $this->postJson('/api/admin/job-posts', [
            'company_id' => $companyId, 'title' => 'Analyst', 'location' => 'Semarang',
            'job_type' => 'Full-time', 'work_type' => 'On-site',
        ])->assertCreated()->json('data.id');

        $seeker = User::factory()->create(['role' => 'jobseeker']);
        $this->actingAs($seeker)->postJson("/api/job-posts/{$jobId}/apply", [])->assertStatus(422);
        $this->postJson('/api/jobseeker/profile', [
            'full_name' => 'Pencari Kerja', 'domicile' => 'Semarang', 'birth_date' => '2000-01-01',
            'gender' => 'female', 'last_education' => 'S1',
        ])->assertCreated();
        $this->postJson("/api/job-posts/{$jobId}/apply", [])->assertStatus(422);
        $this->postJson('/api/jobseeker/profile/details', ['skills' => ['SQL']])->assertOk();
        $this->postJson("/api/job-posts/{$jobId}/apply", [])->assertStatus(422);
        $this->post('/api/jobseeker/profile/cv', [
            'cv' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();
        $this->postJson("/api/job-posts/{$jobId}/apply", [])->assertCreated()->assertJsonPath('status', 'submitted');
        $this->postJson("/api/job-posts/{$jobId}/apply", [])->assertStatus(409);

        $this->assertSame(1, DB::table('applications')->where('job_posts_id', $jobId)->count());
    }
}
