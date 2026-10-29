<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_headings_and_comparison_table_without_exposing_html(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $body = ['blocks' => [
            ['type' => 'paragraph', 'text' => 'Pembuka artikel.'],
            ['type' => 'heading', 'level' => 2, 'text' => 'Perbandingan pilihan'],
            ['type' => 'table', 'headers' => ['Aspek', 'Pilihan A', 'Pilihan B'],
                'rows' => [['Biaya', '<script>alert(1)</script>', 'Terjangkau']]],
        ]];
        $article = $this->actingAs($admin)->postJson('/api/admin/articles', [
            'title' => 'Panduan kerja', 'status' => 'published', 'body_json' => json_encode($body),
        ])->assertCreated()->json('data');

        $this->assertSame('Pembuka artikel.'."\n".'Perbandingan pilihan'."\n".'Aspek Pilihan A Pilihan B'."\n".'Biaya <script>alert(1)</script> Terjangkau', $article['content']);
        $this->get('/articles/'.$article['slug'])->assertOk()
            ->assertSee('<h2>Perbandingan pilihan</h2>', false)
            ->assertSee('<th scope="col">Aspek</th>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/admin/articles/'.$article['id'].'/edit')->assertOk()
            ->assertSee('Tabel Perbandingan')->assertSee('body_json')
            ->assertSee('article-editor-toolbar', false);
    }

    public function test_inline_image_is_private_until_published_and_removed_file_is_cleaned(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $article = $this->actingAs($admin)->postJson('/api/admin/articles', [
            'title' => 'Artikel bergambar', 'status' => 'draft',
            'body_json' => json_encode(['blocks' => [['type' => 'paragraph', 'text' => 'Awal.']]]),
        ])->assertCreated()->json('data');
        $id = $article['id'];
        $slug = $article['slug'];

        $uploaded = $this->post('/api/admin/articles/'.$id.'/images', [
            'image' => UploadedFile::fake()->image('inside.png'),
        ], ['Accept' => 'application/json'])->assertCreated()->json();
        $file = $uploaded['file'];
        $path = "article-media/{$id}/{$file}";
        Storage::disk('local')->assertExists($path);
        $this->get('/api/admin/articles/'.$id.'/images/'.$file)->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->get('/articles/'.$slug.'/images/'.$file)->assertNotFound();

        $body = ['blocks' => [
            ['type' => 'paragraph', 'text' => 'Awal.'],
            ['type' => 'image', 'file' => $file, 'alt' => 'Tim bekerja', 'caption' => 'Di kantor'],
            ['type' => 'heading', 'level' => 3, 'text' => 'Bagian akhir'],
        ]];
        $this->putJson('/api/admin/articles/'.$id, [
            'status' => 'published', 'body_json' => json_encode($body),
        ])->assertOk();
        $this->get('/articles/'.$slug)->assertOk()->assertSee('alt="Tim bekerja"', false)
            ->assertSee('Di kantor');
        $this->get('/articles/'.$slug.'/images/'.$file)->assertOk()->assertHeader('Content-Type', 'image/webp');

        $this->putJson('/api/admin/articles/'.$id, ['status' => 'draft'])->assertOk();
        $this->get('/articles/'.$slug)->assertNotFound();
        $this->get('/articles/'.$slug.'/images/'.$file)->assertNotFound();
        $this->putJson('/api/admin/articles/'.$id, ['status' => 'published'])->assertOk();

        $this->putJson('/api/admin/articles/'.$id, [
            'body_json' => json_encode(['blocks' => [['type' => 'paragraph', 'text' => 'Tanpa gambar.']]]),
        ])->assertOk();
        Storage::disk('local')->assertMissing($path);
        $this->get('/articles/'.$slug.'/images/'.$file)->assertNotFound();
    }

    public function test_invalid_or_foreign_media_is_rejected_and_roles_remain_protected(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $article = $this->actingAs($admin)->postJson('/api/admin/articles', [
            'title' => 'Artikel aman', 'content' => 'Teks lama', 'status' => 'draft',
        ])->assertCreated()->json('data');
        $id = $article['id'];

        $this->putJson('/api/admin/articles/'.$id, [
            'body_json' => json_encode(['blocks' => [['type' => 'image', 'file' => '../secret.webp', 'alt' => 'Rahasia']]]),
        ])->assertUnprocessable()->assertJsonValidationErrors('body_json');
        $this->putJson('/api/admin/articles/'.$id, [
            'body_json' => json_encode(['blocks' => [['type' => 'html', 'html' => '<script>alert(1)</script>']]]),
        ])->assertUnprocessable()->assertJsonValidationErrors('body_json');
        $this->putJson('/api/admin/articles/'.$id, [
            'status' => 'published', 'body_json' => json_encode(['blocks' => []]),
        ])->assertUnprocessable()->assertJsonValidationErrors('content');
        $this->post('/api/admin/articles/'.$id.'/images', [
            'image' => UploadedFile::fake()->create('note.txt', 1, 'text/plain'),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image');

        foreach (['employer', 'jobseeker'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->postJson('/api/admin/articles/'.$id.'/images')->assertForbidden();
            $this->get('/api/admin/articles/'.$id.'/images/00000000-0000-0000-0000-000000000000.webp')->assertForbidden();
        }
        $this->assertDatabaseHas('articles', ['id' => $id, 'status' => 'draft', 'content' => 'Teks lama']);
    }

    public function test_legacy_text_update_replaces_structured_body_and_delete_cleans_media_directory(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $article = $this->actingAs($admin)->postJson('/api/admin/articles', [
            'title' => 'Artikel lama', 'content' => 'Teks lama', 'status' => 'draft',
        ])->assertCreated()->json('data');
        $id = $article['id'];
        $this->get('/admin/articles/'.$id.'/edit')->assertOk()->assertSee('Teks lama');
        $this->postJson('/api/admin/articles/'.$id.'/images', [])->assertUnprocessable();
        $file = $this->post('/api/admin/articles/'.$id.'/images', [
            'image' => UploadedFile::fake()->image('inside.jpg'),
        ], ['Accept' => 'application/json'])->assertCreated()->json('file');
        $path = "article-media/{$id}/{$file}";
        $this->putJson('/api/admin/articles/'.$id, [
            'body_json' => json_encode(['blocks' => [
                ['type' => 'paragraph', 'text' => 'Isi baru'],
                ['type' => 'image', 'file' => $file, 'alt' => 'Foto'],
            ]]),
        ])->assertOk();
        $this->putJson('/api/admin/articles/'.$id, ['content' => 'Kembali ke teks biasa'])->assertOk();
        $this->assertNull(DB::table('articles')->where('id', $id)->value('body_json'));
        Storage::disk('local')->assertMissing($path);

        $secondFile = $this->post('/api/admin/articles/'.$id.'/images', [
            'image' => UploadedFile::fake()->image('leftover.png'),
        ], ['Accept' => 'application/json'])->assertCreated()->json('file');
        Storage::disk('local')->assertExists("article-media/{$id}/{$secondFile}");
        $this->deleteJson('/api/admin/articles/'.$id)->assertNoContent();
        Storage::disk('local')->assertMissing("article-media/{$id}/{$secondFile}");
    }
}
