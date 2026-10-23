<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('job_posts', 'inactive_reason')) {
            Schema::table('job_posts', function (Blueprint $table): void {
                $table->string('inactive_reason')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('job_posts', 'inactive_reason')) {
            Schema::table('job_posts', function (Blueprint $table): void {
                $table->dropColumn('inactive_reason');
            });
        }
    }
};