<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('skills')->select('profile_id')
            ->whereNotNull('name')
            ->groupBy('profile_id', DB::raw('LOWER(TRIM(name))'))
            ->havingRaw('COUNT(*) > 1')->exists();

        if ($duplicates) {
            throw new RuntimeException('Ada skill duplikat pada profil lama. Selesaikan konflik secara manual sebelum migrasi; tidak ada data yang dihapus.');
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('CREATE UNIQUE INDEX skills_profile_name_unique ON skills (profile_id, ((LOWER(TRIM(name)))))');
        } else {
            DB::statement('CREATE UNIQUE INDEX skills_profile_name_unique ON skills (profile_id, LOWER(TRIM(name)))');
        }
    }

    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table): void {
            $table->dropUnique('skills_profile_name_unique');
        });
    }
};
