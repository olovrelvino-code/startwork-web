<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('certificates', 'file_path')) {
            Schema::table('certificates', function (Blueprint $table): void {
                $table->string('file_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Keep uploaded paths and legacy URLs on rollback.
    }
};
