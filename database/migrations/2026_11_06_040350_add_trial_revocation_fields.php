<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_subscriptions', function (Blueprint $table) {
            $table->enum('status', ['pending', 'scheduled', 'active', 'expired', 'cancelled', 'replaced', 'revoked'])->default('pending')->change();
            $table->timestamp('revoked_at')->nullable();
            $table->uuid('revoked_by')->nullable();
            $table->string('revocation_reason', 500)->nullable();
            $table->foreign('revoked_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('company_subscriptions')->where('status', 'revoked')->exists()) {
            throw new LogicException('Rollback would discard trial revocation history.');
        }

        Schema::table('company_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['revoked_by']);
            $table->dropColumn(['revoked_at', 'revoked_by', 'revocation_reason']);
            $table->enum('status', ['pending', 'scheduled', 'active', 'expired', 'cancelled', 'replaced'])->default('pending')->change();
        });

    }
};
