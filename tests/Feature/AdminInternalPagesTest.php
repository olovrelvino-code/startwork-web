<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminInternalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_require_login_and_admin_role(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));

        $employer = User::factory()->create(['role' => 'employer']);
        $this->actingAs($employer)->get('/admin/dashboard')->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Operasional hari ini');
    }

    public function test_admin_login_opens_internal_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password_hash' => Hash::make('example-password'),
        ]);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'example-password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_render_each_core_page_with_empty_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        foreach (['companies', 'job-posts', 'job-posts/create', 'applicants', 'articles', 'articles/create', 'packages'] as $path) {
            $this->get('/admin/'.$path)->assertOk();
        }
    }

    public function test_company_monitoring_shows_employer_data_without_edit_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $companyId = (string) Str::uuid();
        DB::table('companies')->insert([
            'id' => $companyId,
            'owner_user_id' => $employer->id,
            'created_by' => $employer->id,
            'name' => 'Mitra Nusantara',
            'location' => 'Semarang',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/companies')->assertOk()->assertSee('Mitra Nusantara')->assertSee('Milik Employer');
        $this->get('/admin/companies/'.$companyId)->assertOk()->assertSee('Mitra Nusantara')->assertDontSee('Ubah profil');
    }

    public function test_admin_can_open_owned_job_and_article_edit_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'Studio Kerja', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');
        $jobId = $this->postJson('/api/admin/job-posts', [
            'company_id' => $companyId, 'title' => 'Analis Data', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid',
        ])->assertCreated()->json('data.id');
        $articleId = $this->postJson('/api/admin/articles', [
            'title' => 'Panduan karier', 'content' => 'Isi artikel.', 'status' => 'draft',
        ])->assertCreated()->json('data.id');

        $this->get('/admin/companies/'.$companyId)->assertOk()->assertSee('Ubah profil');
        $this->get('/admin/job-posts/'.$jobId)->assertOk()->assertSee('Analis Data');
        $this->get('/admin/job-posts/'.$jobId.'/edit')->assertOk()->assertSee('Simpan perubahan');
        $this->get('/admin/articles/'.$articleId.'/edit')->assertOk()->assertSee('Panduan karier');
    }

    public function test_admin_cannot_open_another_admins_job_edit_page(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->actingAs($owner)->postJson('/api/admin/companies', [
            'name' => 'Studio Kerja', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');
        $jobId = $this->postJson('/api/admin/job-posts', [
            'company_id' => $companyId, 'title' => 'Analis Data', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid',
        ])->assertCreated()->json('data.id');

        $this->actingAs($otherAdmin)->get('/admin/job-posts/'.$jobId.'/edit')->assertNotFound();
    }

    public function test_article_cover_upload_is_stored_and_rejects_wrong_file_type(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/api/admin/articles', [
            'title' => 'Persiapan wawancara',
            'content' => 'Langkah persiapan kerja.',
            'status' => 'draft',
            'cover_file' => UploadedFile::fake()->image('cover.jpg'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $coverPath = $response->json('data.cover_image');
        Storage::disk('public')->assertExists($coverPath);
        $this->assertDatabaseHas('articles', ['title' => 'Persiapan wawancara', 'cover_image' => $coverPath]);

        $articleId = $response->json('data.id');
        $updated = $this->post('/api/admin/articles/'.$articleId, [
            '_method' => 'PUT',
            'cover_file' => UploadedFile::fake()->image('new-cover.png'),
        ], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertExists($updated->json('data.cover_image'));
        Storage::disk('public')->assertMissing($coverPath);

        $this->post('/api/admin/articles', [
            'title' => 'Artikel kedua',
            'content' => 'Konten',
            'status' => 'draft',
            'cover_file' => UploadedFile::fake()->create('note.txt', 1, 'text/plain'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('cover_file');
    }

    public function test_applicant_api_pages_filtered_results_without_losing_total(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $jobseeker = User::factory()->create(['role' => 'jobseeker']);
        $companyId = (string) Str::uuid();
        $profileId = (string) Str::uuid();
        DB::table('companies')->insert([
            'id' => $companyId, 'owner_user_id' => $admin->id, 'created_by' => $admin->id,
            'name' => 'Studio Kerja', 'location' => 'Jakarta', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('jobseeker_profiles')->insert([
            'id' => $profileId, 'user_id' => $jobseeker->id, 'full_name' => 'Siti Pramesti',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $olderApplicationId = (string) Str::uuid();
        foreach ([[$olderApplicationId, now()->subDay()], [(string) Str::uuid(), now()]] as [$applicationId, $appliedAt]) {
            $jobId = (string) Str::uuid();
            DB::table('job_posts')->insert([
                'id' => $jobId, 'company_id' => $companyId, 'posted_by' => $admin->id,
                'title' => 'Analis Data', 'location' => 'Jakarta', 'job_type' => 'Full-time',
                'work_type' => 'Hybrid', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('applications')->insert([
                'id' => $applicationId, 'job_posts_id' => $jobId, 'profile_id' => $profileId,
                'status' => 'submitted', 'applied_at' => $appliedAt,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->actingAs($admin)->getJson('/api/admin/applicants?name=Siti&company_id='.$companyId.'&limit=1&page=2')
            ->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.application_id', $olderApplicationId);
    }

    public function test_admin_can_clear_external_apply_url_to_accept_internal_applications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'Studio Kerja', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');
        $jobId = $this->postJson('/api/admin/job-posts', [
            'company_id' => $companyId, 'title' => 'Analis Data', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid', 'apply_url' => 'https://example.test/apply',
        ])->assertCreated()->json('data.id');

        $this->putJson('/api/admin/job-posts/'.$jobId, ['apply_url' => null])
            ->assertOk()->assertJsonPath('data.apply_url', null);
    }

    public function test_admin_job_expiry_round_trips_between_wib_and_utc(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'Studio Kerja', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');

        $jobId = $this->postJson('/api/admin/job-posts', [
            'company_id' => $companyId, 'title' => 'Analis Data', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'Hybrid',
            'expires_at' => '2030-01-01T17:00+07:00',
        ])->assertCreated()->assertJsonPath('data.expires_at', '2030-01-01 10:00:00')->json('data.id');

        $this->get('/admin/job-posts/'.$jobId.'/edit')->assertOk()->assertSee('value="2030-01-01T17:00"', false);
        $this->get('/admin/job-posts/'.$jobId)->assertOk()->assertSee('01 Jan 2030, 17:00');

        $this->putJson('/api/admin/job-posts/'.$jobId, [
            'expires_at' => '2030-01-02T09:30+07:00',
        ])->assertOk()->assertJsonPath('data.expires_at', '2030-01-02 02:30:00');
    }

    public function test_failed_cover_upload_keeps_existing_cover(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $articleId = $this->actingAs($admin)->postJson('/api/admin/articles', [
            'title' => 'Artikel', 'content' => 'Isi', 'status' => 'draft',
            'cover_image' => 'article-covers/existing.jpg',
        ])->assertCreated()->json('data.id');

        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->once()->andReturn($disk);

        $this->post('/api/admin/articles/'.$articleId, [
            '_method' => 'PUT',
            'cover_file' => UploadedFile::fake()->image('replacement.jpg'),
        ], ['Accept' => 'application/json'])->assertStatus(500);

        $this->assertDatabaseHas('articles', [
            'id' => $articleId, 'cover_image' => 'article-covers/existing.jpg',
        ]);
    }

    public function test_shared_cover_is_deleted_only_after_last_article_stops_using_it(): void
    {
        Storage::fake('public');
        $coverPath = 'article-covers/shared.jpg';
        Storage::disk('public')->put($coverPath, 'image');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $firstId = $this->postJson('/api/admin/articles', [
            'title' => 'Pertama', 'content' => 'Isi', 'status' => 'draft',
            'cover_image' => $coverPath,
        ])->assertCreated()->json('data.id');
        $secondId = $this->postJson('/api/admin/articles', [
            'title' => 'Kedua', 'content' => 'Isi', 'status' => 'draft',
            'cover_image' => $coverPath,
        ])->assertCreated()->json('data.id');

        $this->post('/api/admin/articles/'.$firstId, [
            '_method' => 'PUT',
            'cover_file' => UploadedFile::fake()->image('replacement.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertExists($coverPath);

        $this->deleteJson('/api/admin/articles/'.$secondId)->assertNoContent();
        Storage::disk('public')->assertMissing($coverPath);
    }
}
