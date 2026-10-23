<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StartWorkAdminCompanyClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_employer_company_flows_are_separate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);

        $adminCompany = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'Formulatrix', 'location' => 'Semarang',
        ])->assertCreated()->assertJsonPath('data.owner_user_id', $admin->id)->json('data.id');
        $adminJob = $this->postJson('/api/admin/job-posts', [
            'company_id' => $adminCompany, 'title' => 'QA Engineer',
            'location' => 'Semarang', 'job_type' => 'Full-time', 'work_type' => 'On-site',
        ])->assertCreated()->json('data.id');

        $this->actingAs($employer)->postJson('/api/employer/company', [
            'name' => 'Formulatrix', 'location' => 'Semarang',
        ])->assertCreated()->assertJsonPath('data.owner_user_id', $employer->id);
        $this->getJson('/api/employer/job-posts')->assertJsonPath('total', 0);
        $this->putJson("/api/employer/job-posts/{$adminJob}", ['title' => 'Blocked'])->assertNotFound();
        $this->actingAs($admin)->postJson("/api/admin/companies/{$adminCompany}/assign-employer", [
            'employer_user_id' => $employer->id,
        ])->assertStatus(410);
    }

    public function test_company_logo_is_uploaded_as_a_file_and_owned_by_each_role(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $adminCompany = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'Admin Company', 'location' => 'Semarang',
        ])->assertCreated()->json('data.id');
        $this->post('/api/admin/companies/'.$adminCompany.'/logo', [
            'logo' => UploadedFile::fake()->image('fmlx.png'),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.logo', 'fmlx.png');
        $this->get("/api/companies/{$adminCompany}/logo")->assertOk();

        $employer = User::factory()->create(['role' => 'employer']);
        $this->actingAs($employer)->postJson('/api/employer/company', [
            'name' => 'Employer Company', 'location' => 'Bogor',
        ])->assertCreated();
        $this->post('/api/employer/company/logo', [
            'logo' => UploadedFile::fake()->image('employer.png'),
        ], ['Accept' => 'application/json'])->assertOk();
        $this->getJson('/api/employer/company')->assertOk()->assertJsonPath('data.owner_user_id', $employer->id);
    }
}
