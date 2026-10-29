<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StartWorkArticlePublicAndWebpTest extends TestCase
{
    use RefreshDatabase;

    public function test_logo_upload_converts_allowed_images_and_preserves_old_logo_on_failure(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'Studio Baru', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');

        foreach (['jpg', 'jpeg', 'png'] as $extension) {
            $response = $this->post('/api/admin/companies/'.$companyId.'/logo', [
                'logo' => UploadedFile::fake()->image("logo.{$extension}")->size(2048),
            ], ['Accept' => 'application/json'])->assertOk();
            $name = $response->json('data.logo');
            $this->assertStringEndsWith('.webp', $name);
            $path = "company-logos/{$companyId}/{$name}";
            Storage::disk('public')->assertExists($path);
            $this->assertSame('image/webp', getimagesizefromstring(Storage::disk('public')->get($path))['mime']);
        }
        $old = DB::table('companies')->where('id', $companyId)->value('logo');
        $this->post('/api/admin/companies/'.$companyId.'/logo', [
            'logo' => UploadedFile::fake()->image('too-large.png')->size(2049),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('logo');
        $this->post('/api/admin/companies/'.$companyId.'/logo', [
            'logo' => UploadedFile::fake()->createWithContent('fake.jpg', 'not an image'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('logo');
        $this->post('/api/admin/companies/'.$companyId.'/logo', [
            'logo' => UploadedFile::fake()->image('too-wide.png', 5001, 1),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('logo');
        $this->assertSame($old, DB::table('companies')->where('id', $companyId)->value('logo'));
        Storage::disk('public')->assertExists("company-logos/{$companyId}/{$old}");
        $this->get("/api/companies/{$companyId}/logo")->assertOk()->assertHeader('Content-Type', 'image/webp');
    }

    public function test_employer_logo_uses_same_conversion_and_ownership(): void
    {
        Storage::fake('public');
        $employer = User::factory()->create(['role' => 'employer']);
        $companyId = $this->actingAs($employer)->postJson('/api/employer/company', [
            'name' => 'Employer Baru', 'location' => 'Bandung',
        ])->assertCreated()->json('data.id');
        $name = $this->post('/api/employer/company/logo', [
            'logo' => UploadedFile::fake()->image('employer.png'),
        ], ['Accept' => 'application/json'])->assertOk()->json('data.logo');
        $this->assertStringEndsWith('.webp', $name);
        Storage::disk('public')->assertExists("company-logos/{$companyId}/{$name}");
    }

    public function test_indexed_png_logo_is_converted_and_scaled_with_transparency(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->actingAs($admin)->postJson('/api/admin/companies', [
            'name' => 'Studio Palet', 'location' => 'Jakarta',
        ])->assertCreated()->json('data.id');

        $palette = imagecreate(1200, 400);
        $background = imagecolorallocate($palette, 255, 255, 255);
        imagecolortransparent($palette, $background);
        imagefilledrectangle($palette, 100, 100, 1100, 300, imagecolorallocate($palette, 20, 40, 60));
        ob_start();
        imagepng($palette);
        $png = ob_get_clean();
        imagedestroy($palette);

        $name = $this->post('/api/admin/companies/'.$companyId.'/logo', [
            'logo' => UploadedFile::fake()->createWithContent('indexed.png', $png),
        ], ['Accept' => 'application/json'])->assertOk()->json('data.logo');
        $webp = Storage::disk('public')->get("company-logos/{$companyId}/{$name}");
        [$width, $height] = getimagesizefromstring($webp);
        $this->assertLessThanOrEqual(512, max($width, $height));
        $decoded = imagecreatefromstring($webp);
        $this->assertGreaterThanOrEqual(120, imagecolorsforindex($decoded, imagecolorat($decoded, 0, 0))['alpha']);
        imagedestroy($decoded);
    }

    public function test_public_articles_show_only_published_and_escape_content(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $draft = $this->actingAs($admin)->postJson('/api/admin/articles', [
            'title' => 'Catatan tersembunyi', 'content' => 'Belum terbit', 'status' => 'draft',
        ])->assertCreated()->json('data');
        $published = $this->post('/api/admin/articles', [
            'title' => 'Persiapan wawancara', 'content' => '<script>alert(1)</script>Isi artikel',
            'excerpt' => '<img src=x onerror=alert(1)>',
            'status' => 'published', 'cover_file' => UploadedFile::fake()->image('cover.png'),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data');
        $this->assertStringEndsWith('.webp', $published['cover_image']);
        Storage::disk('local')->assertExists($published['cover_image']);
        Storage::disk('public')->assertMissing($published['cover_image']);
        $this->post('/api/admin/articles', [
            'title' => 'Sampul terlalu besar', 'content' => 'Isi', 'status' => 'draft',
            'cover_file' => UploadedFile::fake()->image('large.jpg')->size(2049),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('cover_file');
        $this->post('/logout');

        $this->get('/articles')->assertOk()->assertSee('Persiapan wawancara')->assertDontSee('Catatan tersembunyi');
        $this->get('/articles/'.$draft['slug'])->assertNotFound();
        $this->get('/articles/'.$draft['slug'].'/cover')->assertNotFound();
        $this->get('/articles/'.$published['slug'])->assertOk()->assertSee('Isi artikel')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
        $this->get('/articles/'.$published['slug'].'/cover')->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->get('/admin/articles')->assertRedirect();
        $this->getJson('/api/admin/articles')->assertUnauthorized();
    }

    public function test_slug_is_unique_and_published_can_return_to_draft(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson('/api/admin/articles', [
            'title' => 'Artikel pertama', 'slug' => 'panduan-kerja', 'content' => 'Isi pertama', 'status' => 'published',
        ])->assertCreated();
        $this->postJson('/api/admin/articles', [
            'title' => 'Artikel kedua', 'slug' => 'panduan-kerja', 'content' => 'Isi kedua', 'status' => 'draft',
        ])->assertUnprocessable()->assertJsonValidationErrors('slug');
        $id = DB::table('articles')->where('slug', 'panduan-kerja')->value('id');
        $this->putJson('/api/admin/articles/'.$id, ['status' => 'draft'])->assertOk();
        $this->get('/articles/panduan-kerja')->assertNotFound();
        $this->get('/articles')->assertDontSee('Artikel pertama');
        $this->actingAs(User::factory()->create(['role' => 'employer']))
            ->postJson('/api/admin/articles', ['title' => 'Ditolak'])->assertForbidden();
    }
}
