<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StartWorkAdminCompanyClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_company_and_jobs_are_available_after_employer_assignment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $companyResponse = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'Formulatrix',
            'location' => 'Semarang',
            'logo' => 'https://example.com/formulatrix.png',
        ])->assertCreated()
            ->assertJsonPath('data.owner_user_id', null)
            ->assertJsonPath('data.created_by', $admin->id);
        $companyId = $companyResponse->json('data.id');

        $jobResponse = $this->actingAs($admin)->postJson('/api/admin/job-posts', [
            'company_id' => $companyId,
            'title' => 'Software Engineer',
            'location' => 'Semarang',
            'job_type' => 'Full-time',
            'work_type' => 'On-site',
        ])->assertCreated()
            ->assertJsonPath('data.company_id', $companyId)
            ->assertJsonPath('data.posted_by', $admin->id);
        $jobId = $jobResponse->json('data.id');

        $this->post('/logout')->assertRedirect('/');
        $registrationResponse = $this->postJson('/api/employer/register', [
            'full_name' => 'Perwakilan Formulatrix',
            'phone' => '081234567890',
            'email' => 'formulatrix@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonPath('data.role', 'employer');
        $employer = User::findOrFail($registrationResponse->json('data.id'));

        $this->actingAs($employer)->getJson('/api/employer/company')->assertNotFound();
        $this->actingAs($employer)->getJson('/api/employer/job-posts')->assertNotFound();

        $this->post('/logout')->assertRedirect('/');
        $this->actingAs($admin)->postJson("/api/admin/companies/{$companyId}/assign-employer", [
            'employer_user_id' => $employer->id,
        ])->assertOk()->assertJsonPath('data.owner_user_id', $employer->id);

        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', [
            'email' => 'formulatrix@example.test',
            'password' => 'password123',
        ])->assertRedirect('/dashboard');

        $this->getJson('/api/employer/company')
            ->assertOk()
            ->assertJsonPath('data.id', $companyId);
        $this->getJson('/api/employer/job-posts')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $jobId)
            ->assertJsonPath('data.0.posted_by', $admin->id);

        $this->assertDatabaseHas('companies', [
            'id' => $companyId,
            'owner_user_id' => $employer->id,
            'created_by' => $admin->id,
        ]);
    }

    public function test_only_admin_can_assign_and_an_owned_company_cannot_be_claimed_again(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $otherEmployer = User::factory()->create(['role' => 'employer']);

        $companyId = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'Formulatrix',
            'location' => 'Semarang',
        ])->assertCreated()->json('data.id');

        $this->actingAs($employer)->postJson("/api/admin/companies/{$companyId}/assign-employer", [
            'employer_user_id' => $employer->id,
        ])->assertForbidden();

        $this->actingAs($admin)->postJson("/api/admin/companies/{$companyId}/assign-employer", [
            'employer_user_id' => $employer->id,
        ])->assertOk();

        $this->actingAs($admin)->postJson("/api/admin/companies/{$companyId}/assign-employer", [
            'employer_user_id' => $otherEmployer->id,
        ])->assertStatus(409);

        $this->assertSame(
            $employer->id,
            DB::table('companies')->where('id', $companyId)->value('owner_user_id')
        );
    }
}
