<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('job_posts')) {
            Schema::create('job_posts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('company_id');
                $table->uuid('posted_by');
                $table->uuid('subscription_id')->nullable();
                $table->string('title')->nullable();
                $table->string('category')->nullable();
                $table->string('location')->nullable();
                $table->string('job_type')->nullable();
                $table->string('work_type')->nullable();
                $table->decimal('salary_min', 15, 2)->nullable();
                $table->decimal('salary_max', 15, 2)->nullable();
                $table->text('description')->nullable();
                $table->text('responsibilities')->nullable();
                $table->text('requirements')->nullable();
                $table->text('skills')->nullable();
                $table->text('benefits')->nullable();
                $table->string('status')->default('active');
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};
