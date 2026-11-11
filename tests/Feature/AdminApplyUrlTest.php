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
        $this->get("/jobs/{$jobId}")->assertOk()
            ->assertSee('href="https://forms.example.test/apply"', false)
            ->assertSee('target="_blank" rel="noopener noreferrer nofollow"', false)
            ->assertSee('>Apply Sekarang</a>', false);
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
            ->assertRedirect('https://mail.google.com/mail/?view=cm&fs=1&to=hr%40example.test');
        $this->get("/jobs/{$jobId}")->assertOk()
            ->assertSee('href="https://mail.google.com/mail/?view=cm&amp;fs=1&amp;to=hr%40example.test"', false);

        $this->actingAs($admin)->put("/api/admin/job-posts/{$jobId}", [
            'apply_url' => 'jobs@example.test',
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('data.apply_url', 'jobs@example.test');
        $this->get("/api/job-posts/{$jobId}/apply-now")
            ->assertRedirect('https://mail.google.com/mail/?view=cm&fs=1&to=jobs%40example.test');
        $this->get("/jobs/{$jobId}")->assertOk()
            ->assertSee('href="https://mail.google.com/mail/?view=cm&amp;fs=1&amp;to=jobs%40example.test"', false);

        $this->actingAs($admin)->put("/api/admin/job-posts/{$jobId}", [
            'apply_url' => 'wa.me/628123456789',
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('data.apply_url', 'wa.me/628123456789');
        $this->get("/jobs/{$jobId}")->assertOk()
            ->assertSee('href="https://wa.me/628123456789"', false);
        $this->get("/api/job-posts/{$jobId}/apply-now")
            ->assertRedirect('https://wa.me/628123456789');
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

    public function test_each_job_uses_its_own_contact_and_invalid_contact_has_feedback(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $companyId, 'name' => 'PT Kontak', 'created_at' => now(), 'updated_at' => now()]);

        foreach (['WhatsApp' => 'wa.me/628123456789', 'Email' => 'hr@example.test', 'Rusak' => 'javascript:alert(1)', 'Internal' => null] as $title => $applyUrl) {
            $jobIds[$title] = (string) Str::uuid();
            DB::table('job_posts')->insert([
                'id' => $jobIds[$title], 'company_id' => $companyId, 'posted_by' => $admin->id,
                'title' => $title, 'job_type' => 'Contract', 'status' => 'active',
                'apply_url' => $applyUrl, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->get('/jobs/'.$jobIds['WhatsApp'])->assertOk()
            ->assertSee('href="https://wa.me/628123456789"', false)
            ->assertDontSee('mailto:hr@example.test');
        $this->get('/api/job-posts/'.$jobIds['WhatsApp'].'/apply-now')
            ->assertRedirect('https://wa.me/628123456789');
        $this->get('/jobs/'.$jobIds['Email'])->assertOk()
            ->assertSee('href="https://mail.google.com/mail/?view=cm&amp;fs=1&amp;to=hr%40example.test"', false)
            ->assertDontSee('https://wa.me/628123456789');
        $this->get('/jobs/'.$jobIds['Rusak'])->assertOk()
            ->assertSee('Kontak pendaftaran lowongan ini tidak valid.')
            ->assertDontSee('href="javascript:', false);
        $this->get('/jobs/'.$jobIds['Internal'])->assertOk()
            ->assertSee('href="'.route('jobs.apply-continue', $jobIds['Internal']).'"', false)
            ->assertSee('>Apply Sekarang</a>', false);
    }
}
