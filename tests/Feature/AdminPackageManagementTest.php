<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPackageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_draft_then_publish_package_for_employer_purchase(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);
        $this->actingAs($admin)->post(route('admin.packages.store'), $this->packageData(['is_active' => '0']))
            ->assertRedirect(route('admin.packages.index', ['status' => 'draft']));
        $package = DB::table('packages')->where('name', 'Promo Rekrutmen')->first();

        $this->assertSame(0, $package->is_active);
        $this->get(route('admin.packages.index', ['status' => 'draft']))->assertOk()->assertSee('Promo Rekrutmen')->assertSee('Tayang di website');
        $this->getJson('/api/packages')->assertDontSee('Promo Rekrutmen');

        $this->patch(route('admin.packages.toggle', $package->id))->assertRedirect(route('admin.packages.index', ['status' => 'active']));
        $this->getJson('/api/packages')->assertSee('Promo Rekrutmen');
        $this->actingAs($employer)->postJson('/api/employer/company', ['name' => 'PT Uji', 'location' => 'Jakarta'])->assertSuccessful();
        $this->postJson(route('employer.subscription.purchases.store'), ['package_id' => $package->id, 'idempotency_key' => (string) Str::uuid()])->assertOk();
        $this->assertDatabaseHas('company_subscriptions', ['package_id' => $package->id, 'quota_total' => 7, 'status' => 'pending']);
        $this->actingAs($admin)->delete(route('admin.packages.destroy', $package->id))->assertStatus(409);
        $this->put(route('admin.packages.update', $package->id), $this->packageData(['name' => 'Nama Pengganti', 'is_active' => '1']))->assertStatus(409);
        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    public function test_admin_can_edit_package_and_delete_only_unused_custom_package(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.packages.store'), $this->packageData(['is_active' => '1']))->assertRedirect();
        $id = DB::table('packages')->where('name', 'Promo Rekrutmen')->value('id');

        $this->put(route('admin.packages.update', $id), $this->packageData(['name' => 'Promo Baru', 'quota' => 12, 'benefits' => "Benefit baru\nBenefit kedua", 'is_active' => '0']))
            ->assertRedirect(route('admin.packages.index', ['status' => 'draft']));
        $this->assertDatabaseHas('packages', ['id' => $id, 'name' => 'Promo Baru', 'quota' => 12, 'benefits' => "Benefit baru\nBenefit kedua", 'is_active' => false]);
        $this->getJson('/api/packages')->assertDontSee('Promo Baru');

        $this->delete(route('admin.packages.destroy', $id))->assertRedirect();
        $this->assertDatabaseMissing('packages', ['id' => $id]);
    }

    public function test_package_writes_require_admin_and_preserve_core_packages(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $this->actingAs($employer)->post(route('admin.packages.store'), $this->packageData())->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.packages.store'), $this->packageData(['quota' => 0]))->assertSessionHasErrors('quota');
        $this->assertDatabaseMissing('packages', ['name' => 'Promo Rekrutmen']);

        $basicId = DB::table('packages')->where('name', 'Basic')->value('id');
        $this->delete(route('admin.packages.destroy', $basicId))->assertStatus(409);
        $this->put(route('admin.packages.update', $basicId), $this->packageData(['name' => 'Basic Baru']))->assertStatus(422);
        $this->assertDatabaseHas('packages', ['id' => $basicId, 'name' => 'Basic']);
    }

    private function packageData(array $changes = []): array
    {
        return array_replace(['name' => 'Promo Rekrutmen', 'quota' => 7, 'duration_days' => 21,
            'price' => 125000, 'benefits' => "Tayang di website\n1x Instagram Story", 'is_active' => '0'], $changes);
    }
}
