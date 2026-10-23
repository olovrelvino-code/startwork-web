<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('applications')) {
            $obsolete = DB::table('applications')->whereIn('status', ['viewed', 'withdrawn'])->count();
            if ($obsolete > 0) {
                throw new RuntimeException('Ubah status viewed/withdrawn yang masih dipakai sebelum migrasi.');
            }
        }

        if (Schema::hasTable('companies') && Schema::hasIndex('companies', 'companies_owner_user_id_unique')) {
            if (! Schema::hasIndex('companies', 'idx_companies_owner')) {
                Schema::table('companies', function (Blueprint $table): void {
                    $table->index('owner_user_id', 'idx_companies_owner');
                });
            }
            Schema::table('companies', function (Blueprint $table): void {
                $table->dropUnique('companies_owner_user_id_unique');
            });
        }

        if (Schema::hasTable('companies')) {
            // Admin-created legacy companies have a creator but no owner.
            DB::table('companies')->whereNull('owner_user_id')->whereNotNull('created_by')
                ->whereIn('created_by', DB::table('users')->where('role', 'admin')->select('id'))
                ->update(['owner_user_id' => DB::raw('created_by')]);
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasTable('applications')) {
            DB::statement("ALTER TABLE applications MODIFY status ENUM('submitted','in_process','accepted','rejected') NOT NULL DEFAULT 'submitted'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' && Schema::hasTable('applications')) {
            DB::statement("ALTER TABLE applications MODIFY status ENUM('submitted','viewed','in_process','accepted','rejected','withdrawn') NOT NULL DEFAULT 'submitted'");
        }

        // Ownership is deliberately retained. Reintroducing UNIQUE would fail when an admin owns multiple companies.
    }
};
