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
        if (! Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('subscription_id');
                $table->string('type')->default('purchase');
                $table->decimal('amount', 12, 2)->nullable();
                $table->decimal('credit_applied', 12, 2)->default(0);
                $table->string('currency', 3)->nullable();
                $table->string('status')->default('pending');
                $table->string('provider')->nullable();
                $table->string('provider_reference')->nullable()->unique();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                $table->foreign('subscription_id')->references('id')->on('company_subscriptions');
            });
        }

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('status', 32)->default('pending_payment')->change();
            $table->string('type', 32)->default('purchase')->change();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->string('proof_path')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_note', 500)->nullable();
            $table->foreign('verified_by')->references('id')->on('users');
        });

        Schema::table('company_subscriptions', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->change();
            $table->unsignedInteger('duration_days')->nullable();
        });

        Schema::create('payment_transaction_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_id');
            $table->uuid('actor_id')->nullable();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at');
            $table->foreign('transaction_id')->references('id')->on('payment_transactions');
            $table->foreign('actor_id')->references('id')->on('users');
            $table->index(['transaction_id', 'created_at']);
        });

        DB::table('packages')->where('name', 'Basic')->update(['price' => 99000, 'quota' => 10, 'duration_days' => 15]);
        DB::table('packages')->where('name', 'Standard')->update(['price' => 149000, 'quota' => 20, 'duration_days' => 30]);
        DB::table('packages')->where('name', 'Premium')->update(['price' => 239000, 'quota' => 30, 'duration_days' => 45]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('payment_transaction_events')->exists() || DB::table('payment_transactions')->whereNotIn('status', ['pending', 'paid', 'failed', 'expired'])->exists()) {
            throw new LogicException('Rollback would discard payment verification history.');
        }

        Schema::dropIfExists('payment_transaction_events');
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['idempotency_key', 'proof_path', 'verified_by', 'verified_at', 'verification_note']);
            $table->enum('status', ['pending', 'paid', 'failed', 'expired'])->default('pending')->change();
            $table->enum('type', ['purchase', 'upgrade', 'renewal'])->default('purchase')->change();
        });
        Schema::table('company_subscriptions', function (Blueprint $table) {
            $table->dropColumn('duration_days');
            $table->enum('status', ['pending', 'scheduled', 'active', 'expired', 'cancelled', 'replaced', 'revoked'])->default('pending')->change();
        });
    }
};
