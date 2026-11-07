<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->char('user_id', 36)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Tidak mengembalikan UUID ke BIGINT
        // untuk menghindari kehilangan data.
    }
};