<?php

use App\JobCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->index(['owner_user_id', 'name'], 'companies_owner_name_index');
        });

        DB::table('job_posts')->whereNotNull('category')->select('category')->distinct()
            ->orderBy('category')->chunk(200, function ($categories): void {
                foreach ($categories as $category) {
                    $name = JobCategory::normalize($category->category);
                    if ($name !== '' && Str::length($name) <= 255) {
                        JobCategory::save($name);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex('companies_owner_name_index');
        });
        Schema::dropIfExists('job_categories');
    }
};
