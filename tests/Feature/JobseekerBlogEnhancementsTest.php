<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class JobseekerBlogEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_search_filters_articles_by_title_or_content_and_shows_search_ui(): void
    {
        $author = User::factory()->create(['role' => 'admin']);

        DB::table('articles')->insert([
            [
                'id' => (string) Str::uuid(),
                'author_id' => $author->id,
                'title' => 'Tips Lolos Interview Kerja',
                'slug' => 'tips-lolos-interview-kerja',
                'excerpt' => 'Pelajari tips interview',
                'content' => 'Langkah persiapan menghadapi sesi wawancara HRD.',
                'status' => 'published',
                'published_at' => now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'author_id' => $author->id,
                'title' => 'Menulis CV yang Menarik',
                'slug' => 'menulis-cv-yang-menarik',
                'excerpt' => 'Panduan membuat resume ATS friendly',
                'content' => 'Format struktur resume dan poin penting pengalaman.',
                'status' => 'published',
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Default: displays both articles and search bar
        $response = $this->get(route('articles.index'))->assertOk();
        $response->assertSee('name="q"', false)
            ->assertSee('aria-label="Cari artikel"', false)
            ->assertSee('Tips Lolos Interview Kerja')
            ->assertSee('Menulis CV yang Menarik');

        // Search by title
        $searchTitle = $this->get(route('articles.index', ['q' => 'Interview']))->assertOk();
        $searchTitle->assertSee('Tips Lolos Interview Kerja')
            ->assertDontSee('Menulis CV yang Menarik')
            ->assertSee('Hasil pencarian untuk');

        // Search by content keyword
        $searchContent = $this->get(route('articles.index', ['q' => 'resume']))->assertOk();
        $searchContent->assertSee('Menulis CV yang Menarik')
            ->assertDontSee('Tips Lolos Interview Kerja');

        // Search with non-matching query shows empty state
        $noMatch = $this->get(route('articles.index', ['q' => 'robotik-xyz']))->assertOk();
        $noMatch->assertSee('Tidak ada artikel yang cocok dengan kata kunci &ldquo;robotik-xyz&rdquo;', false)
            ->assertSee('Lihat semua artikel');
    }

    public function test_article_detail_displays_dynamic_table_of_contents_faq_accordion_and_latest_articles(): void
    {
        $author = User::factory()->create(['role' => 'admin', 'full_name' => 'Redaksi StartWork']);

        $bodyJson = json_encode([
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Pendahuluan artikel tentang dunia kerja.'],
                ['type' => 'heading', 'level' => 2, 'text' => 'Persiapan Dokumen Lamaran'],
                ['type' => 'paragraph', 'text' => 'Uraian persiapan dokumen CV dan portofolio.'],
                ['type' => 'heading', 'level' => 2, 'text' => 'Strategi Saat Wawancara'],
                ['type' => 'paragraph', 'text' => 'Penjelasan teknik menjawab pertanyaan teknis.'],
                ['type' => 'heading', 'level' => 3, 'text' => 'Teknik STAR'],
                ['type' => 'paragraph', 'text' => 'Uraian subtopik wawancara.'],
            ],
        ]);

        $currentArticleId = (string) Str::uuid();
        DB::table('articles')->insert([
            'id' => $currentArticleId,
            'author_id' => $author->id,
            'title' => 'Panduan Meniti Karier Impian',
            'slug' => 'panduan-meniti-karier-impian',
            'excerpt' => 'Langkah menyeluruh mempersiapkan diri di pasar kerja.',
            'content' => "Pendahuluan artikel.\nPersiapan Dokumen Lamaran\nStrategi Saat Wawancara",
            'body_json' => $bodyJson,
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert additional published articles to test latest articles section
        $olderArticleId = (string) Str::uuid();
        DB::table('articles')->insert([
            'id' => $olderArticleId,
            'author_id' => $author->id,
            'title' => 'Tips Negosiasi Gaji untuk Pemula',
            'slug' => 'tips-negosiasi-gaji-untuk-pemula',
            'excerpt' => 'Cara tepat mendiskusikan kompensasi.',
            'content' => 'Isi tips negosiasi.',
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newerArticleId = (string) Str::uuid();
        DB::table('articles')->insert([
            'id' => $newerArticleId,
            'author_id' => $author->id,
            'title' => 'Tren Keterampilan Digital 2027',
            'slug' => 'tren-keterampilan-digital-2027',
            'excerpt' => 'Skill yang paling dicari perusahaan.',
            'content' => 'Isi tren digital.',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('articles.show', 'panduan-meniti-karier-impian'))->assertOk();

        // 1. Table of Contents exists and contains the H2 headings (and not H3)
        $response->assertSee('Daftar Isi')
            ->assertSee(':aria-expanded="open.toString()"', false)
            ->assertSee(":aria-label=\"open ? 'Tutup daftar isi' : 'Buka daftar isi'\"", false)
            ->assertSee('Persiapan Dokumen Lamaran')
            ->assertSee('Strategi Saat Wawancara')
            ->assertSee('#section-persiapan-dokumen-lamaran')
            ->assertSee('#section-strategi-saat-wawancara');

        // Verify order: title appears before Daftar Isi
        $response->assertSeeInOrder([
            'Panduan Meniti Karier Impian',
            'Daftar Isi',
            'Persiapan Dokumen Lamaran',
        ]);

        // 2. FAQ Accordion exists after article content
        $response->assertSee('Pertanyaan Terkait Artikel')
            ->assertSee('name="article-faq"', false)
            ->assertSee('jobseeker-faq', false)
            ->assertSee('Bagaimana cara memanfaatkan wawasan dalam artikel ini untuk mencari kerja?');

        // 3. Latest Articles section exists after FAQ
        $response->assertSee('Artikel Terbaru')
            ->assertSee('Baca Wawasan Lainnya')
            ->assertSee('Tren Keterampilan Digital 2027')
            ->assertSee('Tips Negosiasi Gaji untuk Pemula')
            ->assertSee(route('articles.show', 'tren-keterampilan-digital-2027'))
            ->assertSee(route('articles.show', 'tips-negosiasi-gaji-untuk-pemula'));

        // Verify currently viewed article is NOT displayed in latest articles section
        $content = $response->getContent();
        // Check that latest articles block does not contain a card linking to the current article
        $latestArticlesSection = Str::after($content, 'id="latest-articles-heading"');
        $this->assertStringNotContainsString(route('articles.show', 'panduan-meniti-karier-impian'), $latestArticlesSection);
    }

    public function test_article_detail_without_h2_headings_does_not_render_table_of_contents(): void
    {
        $author = User::factory()->create(['role' => 'admin']);

        DB::table('articles')->insert([
            'id' => (string) Str::uuid(),
            'author_id' => $author->id,
            'title' => 'Artikel Pendek Tanpa Subheading',
            'slug' => 'artikel-pendek-tanpa-subheading',
            'excerpt' => 'Artikel singkat.',
            'content' => 'Teks artikel tanpa ada heading h2 apapun di dalamnya.',
            'body_json' => json_encode(['blocks' => [['type' => 'paragraph', 'text' => 'Teks artikel paragraf tunggal.']]]),
            'status' => 'published',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('articles.show', 'artikel-pendek-tanpa-subheading'))->assertOk();

        // The Daftar Isi container should have display: none or not render heading list
        $response->assertSee('style="display: none;"', false);
    }
}
