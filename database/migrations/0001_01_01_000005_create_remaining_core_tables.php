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
        if (! Schema::hasTable('jobseeker_profiles')) {
            Schema::create('jobseeker_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->unique();
                $table->string('profile_photo')->nullable();
                $table->string('full_name')->nullable();
                $table->string('domicile')->nullable();
                $table->date('birth_date')->nullable();
                $table->string('gender')->nullable();
                $table->string('last_education')->nullable();
                $table->string('portfolio_link')->nullable();
                $table->string('cv_file')->nullable();
                $table->string('cv_filename')->nullable();
                $table->timestamp('cv_uploaded_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('applications')) {
            Schema::create('applications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('job_posts_id');
                $table->uuid('profile_id');
                $table->timestamp('applied_at')->nullable();
                $table->string('status')->default('submitted');
                $table->string('cv_file')->nullable();
                $table->timestamps();

                $table->unique(['job_posts_id', 'profile_id'], 'one_application_per_jobseeker_per_job');
            });
        }

        if (! Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('author_id');
                $table->string('title')->nullable();
                $table->string('cover_image')->nullable();
                $table->text('content')->nullable();
                $table->string('status')->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->unique()->nullable();
                $table->integer('quota')->nullable();
                $table->integer('duration_days')->nullable();
                $table->decimal('price', 12, 2)->default(0);
                $table->string('currency', 3)->default('IDR');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('company_subscriptions')) {
            Schema::create('company_subscriptions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('company_id');
                $table->uuid('package_id');
                $table->uuid('previous_subscription_id')->nullable();
                $table->string('source')->default('purchase');
                $table->string('change_type')->default('new');
                $table->string('status')->default('pending');
                $table->integer('quota_total')->nullable();
                $table->integer('quota_used')->default(0);
                $table->decimal('price_snapshot', 12, 2)->default(0);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->uuid('granted_by_admin_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('jobseeker_profiles');
    }
};
