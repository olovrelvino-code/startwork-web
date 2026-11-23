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

    public function test_public_pages_and_api_report_candidate_csp_and_send_security_headers(): void
    {
        foreach (['/about-us', '/api/packages', '/missing-page'] as $path) {
            $response = $this->get($path);
            $response->assertStatus($path === '/missing-page' ? 404 : 200)
                ->assertHeaderMissing('Content-Security-Policy');
            $policy = $response->headers->get('Content-Security-Policy-Report-Only');

            $this->assertStringContainsString("script-src 'self'", $policy);
            $this->assertStringContainsString("'unsafe-inline'", $policy);
            $this->assertStringNotContainsString("'unsafe-eval'", $policy);
            $this->assertStringContainsString("style-src 'self' 'unsafe-inline' https://fonts.bunny.net", $policy);
            $this->assertStringContainsString("font-src 'self' https://fonts.bunny.net", $policy);
            $this->assertStringContainsString("img-src 'self' data: blob:", $policy);
            $this->assertStringContainsString("connect-src 'self'", $policy);
            $this->assertStringContainsString("frame-src 'none'", $policy);
            $this->assertStringContainsString("object-src 'none'", $policy);
            $this->assertStringContainsString("base-uri 'self'", $policy);
            $response->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');
        }
    }

    public function test_security_headers_cover_authenticated_role_pages_and_uploads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $jobseeker = User::factory()->create(['role' => 'jobseeker']);

        $this->get('/login')->assertOk()->assertHeader('Content-Security-Policy-Report-Only');

        foreach ([
            [$admin, '/admin/dashboard'],
            [$employer, '/employer/setup'],
            [$jobseeker, '/profil'],
        ] as [$user, $path]) {
            $this->actingAs($user)->get($path)
                ->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('Content-Security-Policy-Report-Only')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
                ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');
        }
    }

    public function test_hsts_is_sent_only_for_secure_production_requests(): void
    {
        config(['app.env' => 'production']);

        $this->get('/about-us')->assertOk()->assertHeaderMissing('Strict-Transport-Security');
        $this->withHeader('X-Forwarded-Proto', 'https')->get('/about-us')
            ->assertOk()->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/about-us')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        $this->get('https://localhost/api/packages')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_hsts_is_not_sent_for_secure_testing_requests(): void
    {
        $this->get('https://localhost/about-us')->assertOk()->assertHeaderMissing('Strict-Transport-Security');
    }

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
            'cv' => UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF\n"),
        ], ['Accept' => 'application/json'])->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->postJson("/api/job-posts/{$jobId}/apply", [])->assertCreated()->assertJsonPath('status', 'submitted');
        $this->postJson("/api/job-posts/{$jobId}/apply", [])->assertStatus(409);

        $this->assertSame(1, DB::table('applications')->where('job_posts_id', $jobId)->count());
    }
}
