<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('job_posts', 'apply_url')) {
            Schema::table('job_posts', function (Blueprint $table): void {
                $table->text('apply_url')->nullable()->after('benefits');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('job_posts', 'apply_url')) {
            Schema::table('job_posts', function (Blueprint $table): void {
                $table->dropColumn('apply_url');
            });
        }
    }
};
