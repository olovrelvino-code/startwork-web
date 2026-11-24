<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ->assertSee('data-proof-form', false)
            ->assertSee('JPG, JPEG, PNG, WEBP maks. 20 MB; PDF maks. 2 MB.')
            ->assertSee('Belum ada berkas dipilih.')
            ->assertSee('form="cancel-form-'.$ownId.'"', false)
            ->assertSee('data-proof-toast', false)
            ->assertSee('Bukti pembayaran berhasil dikirim untuk verifikasi admin.')
            ->assertSee(route('employer.subscription.purchases.proof', $ownId))
            ->assertSee(route('employer.subscription.purchases.cancel', $ownId));
        $this->actingAs(User::factory()->create(['role' => 'jobseeker']))
            ->get(route('employer.subscription.purchases.availability'))->assertForbidden();
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
        $this->actingAs($employer)->get(route('employer.dashboard'))->assertOk()->assertSee('Paket Standard Anda sudah aktif. Berlaku hingga');
        $this->actingAs($admin);
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

    public function test_large_image_proof_is_saved_as_webp_under_two_megabytes_for_admin(): void
    {
        Storage::fake('local');
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->company($employer);
        $packageId = $this->package('Basic', 99000, 10, 15);
        $id = $this->actingAs($employer)->postJson(route('employer.subscription.purchases.store'), ['package_id' => $packageId, 'idempotency_key' => (string) Str::uuid()])->assertOk()->json('transaction_id');

        $image = imagecreatetruecolor(2048, 2048);
        for ($y = 0; $y < 2048; $y++) {
            for ($x = 0; $x < 2048; $x++) {
                imagesetpixel($image, $x, $y, mt_rand(0, 0xFFFFFF));
            }
        }
        $source = tempnam(sys_get_temp_dir(), 'proof-');
        imagepng($image, $source);
        imagedestroy($image);
        try {
            $this->assertGreaterThan(2 * 1024 * 1024, filesize($source));
            $this->postJson(route('employer.subscription.purchases.proof', $id), ['proof' => new UploadedFile($source, 'transfer.png', 'image/png', null, true)])->assertOk();
        } finally {
            unlink($source);
        }

        $path = DB::table('payment_transactions')->where('id', $id)->value('proof_path');
        Storage::disk('local')->assertExists($path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertLessThanOrEqual(2 * 1024 * 1024, Storage::disk('local')->size($path));
        $this->assertSame('image/webp', getimagesize(Storage::disk('local')->path($path))['mime']);
        $this->actingAs($admin)->get(route('admin.transactions.proof', $id))->assertOk()->assertHeader('content-type', 'image/webp');
    }

    public function test_proof_rejects_files_over_input_limit_without_changing_transaction(): void
    {
        Storage::fake('local');
        $employer = User::factory()->create(['role' => 'employer']);
        $this->company($employer);
        $packageId = $this->package('Basic', 99000, 10, 15);
        $id = $this->actingAs($employer)->postJson(route('employer.subscription.purchases.store'), ['package_id' => $packageId, 'idempotency_key' => (string) Str::uuid()])->assertOk()->json('transaction_id');

        $this->postJson(route('employer.subscription.purchases.proof', $id), ['proof' => UploadedFile::fake()->image('large.png')->size(20481)])
            ->assertUnprocessable()->assertJsonValidationErrors('proof');
        $this->postJson(route('employer.subscription.purchases.proof', $id), ['proof' => UploadedFile::fake()->create('transfer.pdf', 2049, 'application/pdf')])
            ->assertUnprocessable()->assertJsonValidationErrors('proof');
        $this->assertDatabaseHas('payment_transactions', ['id' => $id, 'status' => 'pending_payment', 'proof_path' => null]);
    }

    #[DataProvider('queuedPackages')]
    public function test_paid_queue_activates_once_after_current_subscription_expires(string $name, int $price, int $quota, int $days): void
    {
        Storage::fake('local');
        $this->travelTo(now()->startOfSecond());
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->company($employer);
        $basic = $this->package('Basic', 99000, 10, 15);
        $selectedPackage = $this->package($name, $price, $quota, $days);
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

        $id = $this->postJson(route('employer.subscription.purchases.store'), ['package_id' => $selectedPackage, 'idempotency_key' => (string) Str::uuid()])->assertOk()->json('transaction_id');
        $this->postJson(route('employer.subscription.purchases.proof', $id), ['proof' => UploadedFile::fake()->image('paid.png')])->assertOk();
        $this->actingAs($admin)->post(route('admin.transactions.decide', $id), ['decision' => 'approve'])->assertRedirect();
        $newId = DB::table('payment_transactions')->where('id', $id)->value('subscription_id');
        $this->assertDatabaseHas('company_subscriptions', ['id' => $newId, 'status' => 'pending_activation', 'quota_used' => 0]);
        $this->actingAs($employer)->postJson(route('employer.subscription.purchases.store'), ['package_id' => $selectedPackage, 'idempotency_key' => (string) Str::uuid()])
            ->assertStatus(409)
            ->assertJsonPath('message', "Trial Anda masih aktif dan paket {$name} sedang menunggu aktivasi. Pembelian berikutnya dapat dilakukan setelah trial berakhir dan paket {$name} aktif.");
        $this->get(route('employer.subscription.purchases.availability'))->assertOk()
            ->assertJsonPath('allowed', false)
            ->assertJsonPath('message', "Trial Anda masih aktif dan paket {$name} sedang menunggu aktivasi. Pembelian berikutnya dapat dilakukan setelah trial berakhir dan paket {$name} aktif.");
        $this->assertSame(2, DB::table('company_subscriptions')->where('company_id', $companyId)->count());
        $this->get(route('employer.subscription'))->assertOk()->assertSee('Pembelian Belum Dapat Dilanjutkan')
            ->assertSee('data-trial-queue-close', false)->assertSee('data-availability-url', false);
        $this->travel(2)->days();
        $this->actingAs($employer)->get(route('employer.dashboard'))->assertOk()
            ->assertSee("Paket {$name} Anda sudah aktif.")
            ->assertDontSee('Pembelian paket Anda masih dalam proses.');
        $this->get(route('employer.subscription.history'))->assertOk()->assertSee('>Aktif<', false);
        $this->assertDatabaseHas('company_subscriptions', ['id' => $oldId, 'status' => 'expired', 'quota_used' => 2]);
        $this->assertDatabaseHas('company_subscriptions', ['id' => $newId, 'package_id' => $selectedPackage, 'status' => 'active', 'quota_total' => $quota, 'quota_used' => 0]);
        $this->assertSame(0, DB::table('company_subscriptions')->where('company_id', $companyId)->where('status', 'pending_activation')->count());
        $this->assertSame(1, DB::table('payment_transaction_events')->where('transaction_id', $id)
            ->where('note', 'Subscription diaktifkan dari antrean')->count());
        $this->assertSame($jobExpiry, DB::table('job_posts')->where('id', $jobId)->value('expires_at'));
        $this->assertSame($days * 86400, strtotime(DB::table('company_subscriptions')->where('id', $newId)->value('expires_at')) - strtotime(DB::table('company_subscriptions')->where('id', $newId)->value('starts_at')));
        $this->get(route('employer.dashboard'))->assertOk()->assertSee("Paket {$name} Anda sudah aktif.")
            ->assertSee(now()->addDays($days)->setTimezone('Asia/Jakarta')->format('d M Y, H:i').' WIB')
            ->assertDontSee('Pilih Paket');
        $this->actingAs($admin)->get(route('admin.transactions.subscriptions', ['status' => 'active']))->assertOk()
            ->assertSee($name)->assertSee(now()->addDays($days)->setTimezone('Asia/Jakarta')->format('d M Y, H:i').' WIB');
        $this->get(route('admin.transactions.subscriptions', ['status' => 'pending_activation']))->assertOk()
            ->assertDontSee('PT Contoh');
        $this->actingAs($employer)->get(route('employer.subscription.purchases.availability'))->assertOk()->assertJsonPath('allowed', true);
    }

    public function test_verification_after_trial_expiry_expires_trial_and_activates_purchase_once(): void
    {
        Storage::fake('local');
        $this->travelTo(now()->startOfSecond());
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->company($employer);
        $basic = $this->package('Basic', 99000, 10, 15);
        $standard = $this->package('Standard', 149000, 20, 30);
        $trialId = (string) Str::uuid();
        DB::table('company_subscriptions')->insert(['id' => $trialId, 'company_id' => $companyId, 'package_id' => $basic,
            'source' => 'admin_trial', 'change_type' => 'trial', 'status' => 'active', 'quota_total' => 10,
            'starts_at' => now()->subDays(15), 'expires_at' => now()->subMinute(), 'created_at' => now(), 'updated_at' => now()]);

        $id = $this->actingAs($employer)->postJson(route('employer.subscription.purchases.store'),
            ['package_id' => $standard, 'idempotency_key' => (string) Str::uuid()])->assertOk()->json('transaction_id');
        $this->postJson(route('employer.subscription.purchases.proof', $id), ['proof' => UploadedFile::fake()->image('paid.png')])->assertOk();
        $this->actingAs($admin)->post(route('admin.transactions.decide', $id), ['decision' => 'approve'])->assertRedirect();
        $this->post(route('admin.transactions.decide', $id), ['decision' => 'approve'])->assertStatus(409);
        $this->actingAs($employer)->get(route('employer.dashboard'))->assertOk();

        $subscriptionId = DB::table('payment_transactions')->where('id', $id)->value('subscription_id');
        $this->assertDatabaseHas('company_subscriptions', ['id' => $trialId, 'status' => 'expired']);
        $this->assertDatabaseHas('company_subscriptions', ['id' => $subscriptionId, 'status' => 'active',
            'starts_at' => now()->toDateTimeString(), 'expires_at' => now()->addDays(30)->toDateTimeString()]);
        $this->assertSame(1, DB::table('company_subscriptions')->where('company_id', $companyId)->where('status', 'active')->count());
        $this->actingAs($employer)->get(route('employer.dashboard'))->assertOk()->assertSee('Paket Standard Anda sudah aktif. Berlaku hingga')
            ->assertDontSee('menunggu verifikasi');
        $this->actingAs($admin)->get(route('admin.transactions.subscriptions', ['status' => 'expired']))->assertOk()->assertSee('PT Contoh');
    }

    public function test_unverified_payment_stays_pending_after_trial_expires(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->company($employer);
        $basic = $this->package('Basic', 99000, 10, 15);
        DB::table('company_subscriptions')->insert(['id' => (string) Str::uuid(), 'company_id' => $companyId,
            'package_id' => $basic, 'source' => 'admin_trial', 'change_type' => 'trial', 'status' => 'active',
            'quota_total' => 10, 'starts_at' => now()->subDays(15), 'expires_at' => now()->subMinute(),
            'created_at' => now(), 'updated_at' => now()]);

        $id = $this->actingAs($employer)->postJson(route('employer.subscription.purchases.store'),
            ['package_id' => $basic, 'idempotency_key' => (string) Str::uuid()])->assertOk()->json('transaction_id');
        $this->actingAs($employer)->get(route('employer.dashboard'))->assertOk();

        $this->assertDatabaseHas('payment_transactions', ['id' => $id, 'status' => 'pending_payment']);
        $this->assertSame(0, DB::table('company_subscriptions')->where('company_id', $companyId)->where('status', 'active')->count());
        $this->actingAs($employer)->get(route('employer.dashboard'))->assertOk()->assertSee('Pembelian paket Anda masih dalam proses.')
            ->assertDontSee('Paket terverifikasi Anda menunggu aktivasi.');
        $this->actingAs($admin)->get(route('admin.transactions.dashboard'))->assertOk();
    }

    /** @return array<string, array{string, int, int, int}> */
    public static function queuedPackages(): array
    {
        return [
            'Basic' => ['Basic', 99000, 10, 15],
            'Standard' => ['Standard', 149000, 20, 30],
            'Premium' => ['Premium', 239000, 30, 45],
        ];
    }

    public function test_ended_trial_with_unfinished_purchase_points_to_purchase_history(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->company($employer);
        $basic = $this->package('Basic', 99000, 10, 15);
        $this->actingAs($admin)->postJson("/api/admin/companies/{$companyId}/grant-trial", ['package_id' => $basic])->assertCreated();
        $this->actingAs($employer)->postJson(route('employer.subscription.purchases.store'), ['package_id' => $basic, 'idempotency_key' => (string) Str::uuid()])->assertOk();

        $this->actingAs($admin)->postJson("/api/admin/companies/{$companyId}/revoke-trial", ['reason' => 'Trial selesai'])->assertOk();
        $this->actingAs($employer)->get(route('employer.dashboard'))->assertOk()
            ->assertSee('Pembelian paket Anda masih dalam proses.')
            ->assertSee('Lihat Riwayat Pembelian')->assertDontSee('Pilih Paket');
    }

    #[DataProvider('queuedPackages')]
    public function test_admin_ending_trial_immediately_activates_purchased_package_and_allows_one_more_purchase(string $name, int $price, int $quota, int $days): void
    {
        Storage::fake('local');
        $this->travelTo(now()->startOfSecond());
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->company($employer);
        $basic = $this->package('Basic', 99000, 10, 15);
        $queued = $this->package($name, $price, $quota, $days);
        $premium = $this->package('Premium', 239000, 30, 45);

        $this->actingAs($admin)->postJson("/api/admin/companies/{$companyId}/grant-trial", ['package_id' => $basic])->assertCreated();
        $trialId = DB::table('company_subscriptions')->where('company_id', $companyId)->where('source', 'admin_trial')->value('id');
        $purchaseId = $this->actingAs($employer)->postJson(route('employer.subscription.purchases.store'), ['package_id' => $queued, 'idempotency_key' => (string) Str::uuid()])->assertOk()->json('transaction_id');
        $this->postJson(route('employer.subscription.purchases.proof', $purchaseId), ['proof' => UploadedFile::fake()->image('transfer.png')])->assertOk();
        $this->actingAs($admin)->post(route('admin.transactions.decide', $purchaseId), ['decision' => 'approve'])->assertRedirect();
        $queuedId = DB::table('payment_transactions')->where('id', $purchaseId)->value('subscription_id');
        $this->assertDatabaseHas('company_subscriptions', ['id' => $queuedId, 'package_id' => $queued, 'status' => 'pending_activation', 'starts_at' => null, 'expires_at' => null]);
        $this->actingAs($employer)->get(route('employer.subscription.history'))->assertOk()->assertSee('Menunggu aktivasi')->assertDontSee('>Aktif<', false);
        $this->actingAs($employer)->get(route('employer.subscription.purchases.availability'))->assertOk()
            ->assertJsonPath('message', "Trial Anda masih aktif dan paket {$name} sedang menunggu aktivasi. Pembelian berikutnya dapat dilakukan setelah trial berakhir dan paket {$name} aktif.");

        $this->actingAs($admin)->postJson("/api/admin/companies/{$companyId}/revoke-trial", ['reason' => 'Trial selesai'])->assertOk();
        $this->assertDatabaseHas('company_subscriptions', ['id' => $trialId, 'status' => 'revoked']);
        $this->assertDatabaseHas('company_subscriptions', ['id' => $queuedId, 'package_id' => $queued, 'status' => 'active', 'quota_total' => $quota, 'quota_used' => 0,
            'starts_at' => now()->toDateTimeString(), 'expires_at' => now()->addDays($days)->toDateTimeString()]);
        $this->assertDatabaseHas('payment_transactions', ['id' => $purchaseId, 'status' => 'paid']);
        $this->actingAs($employer)->get(route('employer.subscription.history'))->assertOk()->assertSee('>Aktif<', false)->assertDontSee('Menunggu aktivasi');
        $this->get(route('employer.dashboard'))->assertOk()->assertSee("Paket {$name} Anda sudah aktif.")->assertDontSee('Pilih Paket');
        $this->actingAs($employer)->get(route('employer.subscription.purchases.availability'))->assertOk()->assertJsonPath('allowed', true);

        $nextId = $this->postJson(route('employer.subscription.purchases.store'), ['package_id' => $premium, 'idempotency_key' => (string) Str::uuid()])->assertOk()->json('transaction_id');
        $this->assertDatabaseHas('payment_transactions', ['id' => $nextId, 'status' => 'pending_payment', 'provider' => 'whatsapp']);
        $this->postJson(route('employer.subscription.purchases.proof', $nextId), ['proof' => UploadedFile::fake()->image('premium.png')])->assertOk();
        $this->actingAs($admin)->post(route('admin.transactions.decide', $nextId), ['decision' => 'approve'])->assertRedirect();
        $this->actingAs($employer)->get(route('employer.subscription.purchases.availability'))->assertOk()->assertJsonPath('allowed', false)
            ->assertJsonPath('message', 'Anda masih memiliki antrean subscription yang menunggu aktivasi. Selesaikan antrean tersebut sebelum membeli paket berikutnya.');
        $this->postJson(route('employer.subscription.purchases.store'), ['package_id' => $premium, 'idempotency_key' => (string) Str::uuid()])
            ->assertStatus(409)->assertJsonPath('message', 'Anda masih memiliki antrean subscription yang menunggu aktivasi. Selesaikan antrean tersebut sebelum membeli paket berikutnya.');
        $this->assertSame(2, DB::table('payment_transactions')->count());
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

    #[DataProvider('queuedPackages')]
    public function test_active_paid_package_expiry_automatically_activates_queued_package(string $name, int $price, int $quota, int $days): void
    {
        Storage::fake('local');
        $this->travelTo(now()->startOfSecond());
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->company($employer);
        $basic = $this->package('Basic', 99000, 10, 15);
        $queuedPackage = $this->package($name, $price, $quota, $days);

        // First active paid subscription
        $firstSubId = (string) Str::uuid();
        $firstTxId = (string) Str::uuid();
        DB::table('company_subscriptions')->insert([
            'id' => $firstSubId, 'company_id' => $companyId, 'package_id' => $basic,
            'source' => 'purchase', 'change_type' => 'new', 'status' => 'active', 'quota_total' => 10, 'quota_used' => 1,
            'duration_days' => 15, 'price_snapshot' => 99000,
            'starts_at' => now()->subDays(14), 'expires_at' => now()->addDay(),
            'created_at' => now()->subDays(14), 'updated_at' => now()->subDays(14),
        ]);
        DB::table('payment_transactions')->insert([
            'id' => $firstTxId, 'subscription_id' => $firstSubId, 'type' => 'purchase', 'amount' => 99000,
            'credit_applied' => 0, 'currency' => 'IDR', 'status' => 'paid', 'provider' => 'whatsapp',
            'paid_at' => now()->subDays(14), 'verified_by' => $admin->id, 'verified_at' => now()->subDays(14),
            'idempotency_key' => (string) Str::uuid(), 'created_at' => now()->subDays(14), 'updated_at' => now()->subDays(14),
        ]);

        // Employer purchases second package into queue
        $this->actingAs($employer);
        $secondTxId = $this->postJson(route('employer.subscription.purchases.store'), ['package_id' => $queuedPackage, 'idempotency_key' => (string) Str::uuid()])
            ->assertOk()->json('transaction_id');
        $this->postJson(route('employer.subscription.purchases.proof', $secondTxId), ['proof' => UploadedFile::fake()->image('proof.png')])->assertOk();

        // Admin approves -> status becomes pending_activation because first package is active
        $this->actingAs($admin)->post(route('admin.transactions.decide', $secondTxId), ['decision' => 'approve'])->assertRedirect();
        $secondSubId = DB::table('payment_transactions')->where('id', $secondTxId)->value('subscription_id');
        $this->assertDatabaseHas('company_subscriptions', ['id' => $secondSubId, 'status' => 'pending_activation']);

        // Third purchase attempt must be rejected by backend
        $this->actingAs($employer)->postJson(route('employer.subscription.purchases.store'), ['package_id' => $basic, 'idempotency_key' => (string) Str::uuid()])
            ->assertStatus(409);

        // Advance time past first package's expires_at
        $this->travel(2)->days();

        // Employer accesses dashboard - transitions automatically without scheduler
        $this->actingAs($employer)->get(route('employer.dashboard'))
            ->assertOk()
            ->assertSee("Paket {$name} Anda sudah aktif.")
            ->assertDontSee('Pilih Paket');

        // Verify database statuses
        $this->assertDatabaseHas('company_subscriptions', ['id' => $firstSubId, 'status' => 'expired']);
        $this->assertDatabaseHas('company_subscriptions', ['id' => $secondSubId, 'status' => 'active', 'quota_total' => $quota, 'quota_used' => 0]);
        $this->assertSame(0, DB::table('company_subscriptions')->where('company_id', $companyId)->where('status', 'pending_activation')->count());

        // Queue is now empty, so 1 more purchase into queue is allowed
        $this->get(route('employer.subscription.purchases.availability'))->assertOk()->assertJsonPath('allowed', true);
    }

    public function test_expired_subscription_without_queue_blocks_job_publishing_and_directs_to_new_package(): void
    {
        $this->travelTo(now()->startOfSecond());
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->company($employer);
        $standard = $this->package('Standard', 149000, 20, 30);

        $subId = (string) Str::uuid();
        $txId = (string) Str::uuid();
        DB::table('company_subscriptions')->insert([
            'id' => $subId, 'company_id' => $companyId, 'package_id' => $standard,
            'source' => 'purchase', 'change_type' => 'new', 'status' => 'active', 'quota_total' => 20, 'quota_used' => 0,
            'duration_days' => 30, 'price_snapshot' => 149000,
            'starts_at' => now()->subDays(31), 'expires_at' => now()->subDay(),
            'created_at' => now()->subDays(31), 'updated_at' => now()->subDays(31),
        ]);
        DB::table('payment_transactions')->insert([
            'id' => $txId, 'subscription_id' => $subId, 'type' => 'purchase', 'amount' => 149000,
            'currency' => 'IDR', 'status' => 'paid', 'provider' => 'whatsapp', 'paid_at' => now()->subDays(31),
            'idempotency_key' => (string) Str::uuid(), 'created_at' => now()->subDays(31), 'updated_at' => now()->subDays(31),
        ]);

        $jobId = (string) Str::uuid();
        DB::table('job_posts')->insert([
            'id' => $jobId, 'company_id' => $companyId, 'posted_by' => $employer->id,
            'title' => 'Lowongan Draft', 'status' => 'inactive', 'location' => 'Jakarta',
            'job_type' => 'Full-time', 'work_type' => 'WFO',
            'expires_at' => now()->addDays(5)->toDateTimeString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($employer);

        // Accessing dashboard marks subscription as expired and directs to buy new package
        $this->get(route('employer.dashboard'))
            ->assertOk()
            ->assertSee('Paket langganan telah berakhir.')
            ->assertSee('Pilih Paket')
            ->assertSee(route('employer.subscription'));

        $this->assertDatabaseHas('company_subscriptions', ['id' => $subId, 'status' => 'expired']);

        // Attempting to publish job post without active subscription must be rejected
        $this->putJson("/api/employer/job-posts/{$jobId}/status", ['status' => 'active'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Subscription aktif tidak ditemukan');

        // Job post create page indicates no active quota
        $this->get(route('employer.job-posts.create'))
            ->assertOk();
    }

    public function test_third_purchase_when_active_and_queue_exist_is_rejected_by_backend(): void
    {
        Storage::fake('local');
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $companyId = $this->company($employer);
        $basic = $this->package('Basic', 99000, 10, 15);
        $standard = $this->package('Standard', 149000, 20, 30);
        $premium = $this->package('Premium', 239000, 30, 45);

        // 1 Active subscription
        $activeSubId = (string) Str::uuid();
        DB::table('company_subscriptions')->insert([
            'id' => $activeSubId, 'company_id' => $companyId, 'package_id' => $basic,
            'source' => 'purchase', 'change_type' => 'new', 'status' => 'active', 'quota_total' => 10, 'quota_used' => 0,
            'duration_days' => 15, 'price_snapshot' => 99000,
            'starts_at' => now(), 'expires_at' => now()->addDays(15),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('payment_transactions')->insert([
            'id' => (string) Str::uuid(), 'subscription_id' => $activeSubId, 'type' => 'purchase', 'amount' => 99000,
            'currency' => 'IDR', 'status' => 'paid', 'provider' => 'whatsapp', 'paid_at' => now(),
            'idempotency_key' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($employer);

        // Purchase 2 (In Queue)
        $tx2 = $this->postJson(route('employer.subscription.purchases.store'), ['package_id' => $standard, 'idempotency_key' => (string) Str::uuid()])
            ->assertOk()->json('transaction_id');

        // While Purchase 2 is unverified (pending_payment), 3rd purchase is rejected
        $this->postJson(route('employer.subscription.purchases.store'), ['package_id' => $premium, 'idempotency_key' => (string) Str::uuid()])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Selesaikan pembelian sebelumnya sebelum membeli paket berikutnya.');

        // Send proof for Purchase 2
        $this->postJson(route('employer.subscription.purchases.proof', $tx2), ['proof' => UploadedFile::fake()->image('proof.png')])->assertOk();

        // Admin approves Purchase 2 -> moves to pending_activation
        $this->actingAs($admin)->post(route('admin.transactions.decide', $tx2), ['decision' => 'approve'])->assertRedirect();

        // Now with 1 Active + 1 In Queue (pending_activation), 3rd purchase must still be rejected
        $this->actingAs($employer)->postJson(route('employer.subscription.purchases.store'), ['package_id' => $premium, 'idempotency_key' => (string) Str::uuid()])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Anda masih memiliki antrean subscription yang menunggu aktivasi. Selesaikan antrean tersebut sebelum membeli paket berikutnya.');

        // Availability endpoint also reports false
        $this->get(route('employer.subscription.purchases.availability'))
            ->assertOk()
            ->assertJsonPath('allowed', false);
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
