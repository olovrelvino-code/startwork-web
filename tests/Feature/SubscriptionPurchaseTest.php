<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_employer_history_shows_only_own_purchases_and_payment_actions(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $other = User::factory()->create(['role' => 'employer']);
        $this->company($employer);
        $this->company($other);
        $packageId = $this->package('Basic', 99000, 10, 15);
        $ownId = $this->actingAs($employer)->postJson(route('employer.subscription.purchases.store'), ['package_id' => $packageId, 'idempotency_key' => (string) Str::uuid()])->assertOk()->json('transaction_id');
        $otherId = $this->actingAs($other)->postJson(route('employer.subscription.purchases.store'), ['package_id' => $packageId, 'idempotency_key' => (string) Str::uuid()])->assertOk()->json('transaction_id');

        $this->actingAs($employer)->get(route('employer.subscription.history'))
            ->assertOk()->assertSee($ownId)->assertDontSee($otherId)
            ->assertSee(route('employer.subscription.purchases.proof', $ownId))
            ->assertSee(route('employer.subscription.purchases.cancel', $ownId));
    }

    public function test_purchase_requires_proof_and_admin_approval_before_activation(): void
    {
        Storage::fake('local');
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->company($employer);
        $packageId = $this->package('Standard', 149000, 20, 30);
        $this->actingAs($employer);

        $key = (string) Str::uuid();
        $id = $this->postJson(route('employer.subscription.purchases.store'), ['package_id' => $packageId, 'idempotency_key' => $key])
            ->assertOk()->json('transaction_id');
        $this->postJson(route('employer.subscription.purchases.store'), ['package_id' => $packageId, 'idempotency_key' => $key])->assertJsonPath('transaction_id', $id);
        $this->postJson(route('employer.subscription.purchases.store'), ['package_id' => $packageId, 'idempotency_key' => (string) Str::uuid()])->assertStatus(409);
        $this->assertDatabaseHas('payment_transactions', ['id' => $id, 'status' => 'pending_payment', 'amount' => 149000]);
        $this->assertDatabaseHas('company_subscriptions', ['company_id' => $companyId, 'status' => 'pending', 'quota_total' => 20]);
        $this->actingAs($admin)->post(route('admin.transactions.decide', $id), ['decision' => 'approve'])->assertStatus(409);
        $this->actingAs($employer)->postJson(route('employer.subscription.purchases.proof', $id), ['proof' => UploadedFile::fake()->image('transfer.png')])->assertOk();
        $this->actingAs($admin)->post(route('admin.transactions.decide', $id), ['decision' => 'approve'])->assertRedirect();
        $this->assertDatabaseHas('payment_transactions', ['id' => $id, 'status' => 'paid', 'verified_by' => $admin->id]);
        $this->assertDatabaseHas('company_subscriptions', ['company_id' => $companyId, 'status' => 'active', 'quota_total' => 20]);
        $this->post(route('admin.transactions.decide', $id), ['decision' => 'approve'])->assertStatus(409);
        $this->assertSame(3, DB::table('payment_transaction_events')->where('transaction_id', $id)->count());
        $this->get(route('admin.transactions.proof', $id))->assertOk();
        foreach (['admin.transactions.dashboard', 'admin.transactions.requests', 'admin.transactions.history', 'admin.transactions.subscriptions'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('admin.transactions.show', $id))->assertOk()->assertSee('PT Contoh');
        $this->actingAs($employer)->get(route('admin.transactions.proof', $id))->assertForbidden();
        $this->get(route('admin.transactions.history'))->assertForbidden();
    }

    public function test_paid_queue_activates_once_after_current_subscription_expires(): void
    {
        Storage::fake('local');
        $this->travelTo(now()->startOfSecond());
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->company($employer);
        $basic = $this->package('Basic', 99000, 10, 15);
        $premium = $this->package('Premium', 239000, 30, 45);
        $oldId = (string) Str::uuid();
        DB::table('company_subscriptions')->insert(['id' => $oldId, 'company_id' => $companyId, 'package_id' => $basic,
            'source' => 'admin_trial', 'change_type' => 'trial', 'status' => 'active', 'quota_total' => 10, 'quota_used' => 2,
            'starts_at' => now()->subDays(14), 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);
        $jobId = (string) Str::uuid();
        $jobExpiry = now()->addDay()->toDateTimeString();
        DB::table('job_posts')->insert(['id' => $jobId, 'company_id' => $companyId, 'posted_by' => $employer->id,
            'subscription_id' => $oldId, 'title' => 'Lowongan lama', 'status' => 'active', 'expires_at' => $jobExpiry,
            'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($employer);

        $id = $this->postJson(route('employer.subscription.purchases.store'), ['package_id' => $premium, 'idempotency_key' => (string) Str::uuid()])->assertOk()->json('transaction_id');
        $this->postJson(route('employer.subscription.purchases.proof', $id), ['proof' => UploadedFile::fake()->image('paid.png')])->assertOk();
        $this->actingAs($admin)->post(route('admin.transactions.decide', $id), ['decision' => 'approve'])->assertRedirect();
        $newId = DB::table('payment_transactions')->where('id', $id)->value('subscription_id');
        $this->assertDatabaseHas('company_subscriptions', ['id' => $newId, 'status' => 'pending_activation', 'quota_used' => 0]);
        $this->actingAs($employer)->postJson(route('employer.subscription.purchases.store'), ['package_id' => $basic, 'idempotency_key' => (string) Str::uuid()])->assertStatus(409);
        $this->travel(2)->days();
        $this->artisan('subscriptions:activate-due')->assertExitCode(0);
        $this->artisan('subscriptions:activate-due')->assertExitCode(0);
        $this->assertDatabaseHas('company_subscriptions', ['id' => $oldId, 'status' => 'expired', 'quota_used' => 2]);
        $this->assertDatabaseHas('company_subscriptions', ['id' => $newId, 'status' => 'active', 'quota_used' => 0]);
        $this->assertSame($jobExpiry, DB::table('job_posts')->where('id', $jobId)->value('expires_at'));
        $this->assertSame(45 * 86400, strtotime(DB::table('company_subscriptions')->where('id', $newId)->value('expires_at')) - strtotime(DB::table('company_subscriptions')->where('id', $newId)->value('starts_at')));
    }

    public function test_rejection_never_creates_an_active_subscription(): void
    {
        Storage::fake('local');
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->company($employer);
        $packageId = $this->package('Premium', 239000, 30, 45);
        $this->actingAs($employer);
        $id = $this->postJson(route('employer.subscription.purchases.store'), ['package_id' => $packageId, 'idempotency_key' => (string) Str::uuid()])->assertOk()->json('transaction_id');
        $this->postJson(route('employer.subscription.purchases.proof', $id), ['proof' => UploadedFile::fake()->image('paid.png')])->assertOk();

        $this->actingAs($admin)->post(route('admin.transactions.decide', $id), ['decision' => 'reject', 'note' => 'Nominal tidak sesuai'])->assertRedirect();
        $this->assertDatabaseHas('payment_transactions', ['id' => $id, 'status' => 'rejected', 'verification_note' => 'Nominal tidak sesuai']);
        $this->assertSame(0, DB::table('company_subscriptions')->where('status', 'active')->count());
    }

    public function test_employer_can_cancel_only_own_unsubmitted_purchase(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $other = User::factory()->create(['role' => 'employer']);
        $this->company($employer);
        $this->company($other);
        $packageId = $this->package('Standard', 149000, 20, 30);
        $id = $this->actingAs($employer)->postJson(route('employer.subscription.purchases.store'), ['package_id' => $packageId, 'idempotency_key' => (string) Str::uuid()])->assertOk()->json('transaction_id');

        $this->actingAs($other)->post(route('employer.subscription.purchases.cancel', $id))->assertNotFound();
        $this->actingAs($employer)->post(route('employer.subscription.purchases.cancel', $id))->assertRedirect();
        $this->assertDatabaseHas('payment_transactions', ['id' => $id, 'status' => 'cancelled']);
        $this->post(route('employer.subscription.purchases.cancel', $id))->assertStatus(409);
    }

    public function test_admin_can_search_subscriptions_and_transactions_by_company_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employerA = User::factory()->create(['role' => 'employer']);
        $employerB = User::factory()->create(['role' => 'employer']);
        $companyAId = (string) Str::uuid();
        $companyBId = (string) Str::uuid();
        DB::table('companies')->insert([
            ['id' => $companyAId, 'owner_user_id' => $employerA->id, 'created_by' => $employerA->id, 'name' => 'PT Alpha Solusi', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $companyBId, 'owner_user_id' => $employerB->id, 'created_by' => $employerB->id, 'name' => 'CV Beta Maju', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $pkgId = $this->package('Standard', 149000, 20, 30);
        $subA = (string) Str::uuid();
        $subB = (string) Str::uuid();
        DB::table('company_subscriptions')->insert([
            ['id' => $subA, 'company_id' => $companyAId, 'package_id' => $pkgId, 'source' => 'purchase', 'change_type' => 'paid', 'status' => 'active', 'quota_total' => 20, 'quota_used' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => $subB, 'company_id' => $companyBId, 'package_id' => $pkgId, 'source' => 'purchase', 'change_type' => 'paid', 'status' => 'active', 'quota_total' => 20, 'quota_used' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $txA = (string) Str::uuid();
        $txB = (string) Str::uuid();
        DB::table('payment_transactions')->insert([
            ['id' => $txA, 'subscription_id' => $subA, 'amount' => 149000, 'status' => 'waiting_verification', 'idempotency_key' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()],
            ['id' => $txB, 'subscription_id' => $subB, 'amount' => 149000, 'status' => 'waiting_verification', 'idempotency_key' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($admin);

        // Subscriptions search
        $this->get(route('admin.transactions.subscriptions', ['search' => 'Alpha']))
            ->assertOk()
            ->assertSee('PT Alpha Solusi')
            ->assertDontSee('CV Beta Maju');

        $this->get(route('admin.transactions.subscriptions', ['search' => 'Beta']))
            ->assertOk()
            ->assertSee('CV Beta Maju')
            ->assertDontSee('PT Alpha Solusi');

        // Requests search
        $this->get(route('admin.transactions.requests', ['search' => 'Alpha']))
            ->assertOk()
            ->assertSee('PT Alpha Solusi')
            ->assertDontSee('CV Beta Maju');
    }

    private function company(User $owner): string
    {
        $id = (string) Str::uuid();
        DB::table('companies')->insert(['id' => $id, 'owner_user_id' => $owner->id, 'created_by' => $owner->id,
            'name' => 'PT Contoh', 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }

    private function package(string $name, int $price, int $quota, int $days): string
    {
        $existing = DB::table('packages')->where('name', $name)->value('id');
        if ($existing !== null) {
            return $existing;
        }
        $id = (string) Str::uuid();
        DB::table('packages')->insert(['id' => $id, 'name' => $name, 'price' => $price, 'quota' => $quota,
            'duration_days' => $days, 'currency' => 'IDR', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }
}
