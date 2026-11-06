<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class JobPostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_and_public_list(): void
    {
        $this->getJson('/api')->assertOk()->assertJsonPath('database', 'connected');
        $this->getJson('/api/job-posts')->assertOk()->assertJsonStructure(['total', 'data']);
    }

    public function test_public_job_cards_can_request_small_payload_without_changing_the_default_api(): void
    {
        $user = User::factory()->create(['role' => 'employer']);
        $companyId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'name' => 'PT Contoh', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('job_posts')->insert([
            'id' => (string) Str::uuid(), 'company_id' => $companyId, 'posted_by' => $user->id,
            'title' => 'Desainer', 'description' => str_repeat('Deskripsi panjang. ', 200),
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->getJson('/api/job-posts')->assertOk()->assertJsonPath('data.0.description', str_repeat('Deskripsi panjang. ', 200));
        $summary = $this->getJson('/api/job-posts?summary=1')->assertOk()->assertJsonPath('data.0.title', 'Desainer')->json('data.0');
        $this->assertArrayNotHasKey('description', $summary);
        $this->assertArrayNotHasKey('responsibilities', $summary);
    }

    public function test_employer_can_create_update_and_delete_own_draft(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $this->actingAs($employer)->postJson('/api/employer/company', [
            'name' => 'PT Uji', 'location' => 'Jakarta',
        ])->assertCreated();

        $id = $this->postJson('/api/employer/job-posts', [
            'title' => 'Backend Developer', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid',
        ])->assertCreated()->assertJsonPath('data.status', 'inactive')->json('data.id');

        $this->putJson("/api/employer/job-posts/{$id}", [
            'title' => 'Senior Backend Developer', 'salary_min' => 12000000,
            'salary_max' => 20000000, 'benefits' => 'BPJS',
        ])->assertOk()->assertJsonPath('data.title', 'Senior Backend Developer')
            ->assertJsonPath('data.salary_min', 12000000);
        $this->getJson('/api/job-posts')->assertJsonPath('total', 0);
        $this->deleteJson("/api/employer/job-posts/{$id}")->assertNoContent();
        $this->getJson('/api/employer/job-posts')->assertJsonPath('total', 0);
    }

    public function test_unscoped_write_routes_are_retired(): void
    {
        $this->postJson('/api/job-posts', [])->assertStatus(410);
        $this->putJson('/api/job-posts/test', [])->assertStatus(410);
        $this->deleteJson('/api/job-posts/test')->assertStatus(410);
    }
}
