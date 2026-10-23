<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminApplyUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_post_external_job_and_button_redirects(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = (string) Str::uuid();
        DB::table('companies')->insert([
            'id' => $companyId,
            'owner_user_id' => $admin->id,
            'created_by' => $admin->id,
            'name' => 'Formulatrix',
            'location' => 'Semarang',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $jobId = $this->actingAs($admin)->postJson('/api/admin/job-posts', [
            'company_id' => $companyId,
            'title' => 'QA Engineer',
            'location' => 'Semarang',
            'job_type' => 'Full-time',
            'work_type' => 'on-site',
            'apply_url' => 'https://forms.example.test/apply',
        ])->assertCreated()
            ->assertJsonPath('data.apply_url', 'https://forms.example.test/apply')
            ->json('data.id');

        $this->assertDatabaseHas('job_posts', [
            'id' => $jobId,
            'apply_url' => 'https://forms.example.test/apply',
        ]);
        $this->get("/jobs/{$jobId}")->assertOk()->assertSee('Apply Sekarang');
        $this->get("/api/job-posts/{$jobId}/apply-now")
            ->assertRedirect('https://forms.example.test/apply');
        $this->postJson("/api/job-posts/{$jobId}/apply", [
            'user_id' => (string) Str::uuid(),
        ])->assertForbidden();

        $this->actingAs($admin)->put("/api/admin/job-posts/{$jobId}", [
            'apply_url' => 'mailto:hr@example.test',
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('data.apply_url', 'mailto:hr@example.test');
        $this->get("/api/job-posts/{$jobId}/apply-now")
            ->assertRedirect('mailto:hr@example.test');
    }

    public function test_admin_cannot_store_unsafe_apply_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson('/api/admin/job-posts', [
            'company_id' => (string) Str::uuid(),
            'title' => 'Unsafe Link',
            'location' => 'Semarang',
            'job_type' => 'Full-time',
            'work_type' => 'on-site',
            'apply_url' => 'javascript:alert(1)',
        ])->assertUnprocessable()->assertJsonValidationErrors('apply_url');
    }
}
