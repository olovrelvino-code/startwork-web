<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->text('excerpt')->nullable();
        });

        DB::table('articles')->select('id', 'title', 'content')->orderBy('id')->chunk(100, function ($articles): void {
            foreach ($articles as $article) {
                $base = trim(Str::substr(Str::slug($article->title ?: 'artikel'), 0, 200), '-') ?: 'artikel';
                DB::table('articles')->where('id', $article->id)->update([
                    'slug' => $base.'-'.$article->id,
                    'excerpt' => Str::limit(trim(strip_tags($article->content ?? '')), 180, ''),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'excerpt']);
        });
    }
};
