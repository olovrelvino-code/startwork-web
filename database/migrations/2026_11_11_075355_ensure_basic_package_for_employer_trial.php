<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('packages')->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'name' => 'Basic',
            'quota' => 10,
            'duration_days' => 15,
            'price' => 99000,
            'currency' => 'IDR',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The package may already exist or be referenced by subscriptions.
    }
};
