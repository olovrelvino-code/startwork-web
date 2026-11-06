<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_employer_sidebar_menu_opens_their_settings_page(): void
    {
        foreach (['admin' => '/admin/dashboard', 'employer' => '/employer/job-posts'] as $role => $dashboard) {
            $user = User::factory()->create(['role' => $role, 'full_name' => 'Pemilik Akun']);

            $this->actingAs($user)->get($dashboard)->assertOk()
                ->assertSee('Pemilik Akun')->assertSee($user->email)
                ->assertSee('href="'.route('profile.edit').'"', false)
                ->assertSee('Log out')->assertSee('role="menu"', false);

            $this->get('/profile')->assertOk()
                ->assertSee($role === 'admin' ? 'admin-internal-shell' : 'employer-shell')
                ->assertSee('Pengaturan akun')->assertSee('Profile')->assertSee('Password')
                ->assertSee('name="name"', false)->assertSee('name="email"', false)
                ->assertSee('name="current_password"', false)
                ->assertDontSee('Appearance')->assertDontSee('Delete account');
        }
    }

    public function test_admin_and_employer_can_update_only_their_own_sidebar_profile(): void
    {
        foreach (['admin' => '/admin/dashboard', 'employer' => '/employer/job-posts'] as $role => $page) {
            $user = User::factory()->create(['role' => $role, 'full_name' => 'Nama Lama']);
            $other = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get($page)->assertOk()
                ->assertSee('id="account-menu-panel"', false)
                ->assertSee('Nama Lama')
                ->assertSee($user->email);

            $this->from($page)->patch('/profile', [
                'name' => 'Nama Baru', 'email' => $role.'@example.com',
            ])->assertRedirect($page)->assertSessionHas('status', 'profile-updated');

            $this->assertSame('Nama Baru', $user->refresh()->full_name);
            $this->assertSame($role.'@example.com', $user->email);
            $this->assertNotSame('Nama Baru', $other->refresh()->full_name);

            $this->from($page)->patch('/profile', [
                'name' => 'Nama Salah', 'email' => $other->email,
            ])->assertSessionHasErrors('email');
            $this->assertSame('Nama Baru', $user->refresh()->full_name);
        }
    }

    public function test_admin_and_employer_password_reset_requires_current_password_and_confirmation(): void
    {
        foreach (['admin', 'employer'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->from('/profile')->put('/password', [
                'current_password' => 'salah', 'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

            $this->from('/profile')->put('/password', [
                'current_password' => 'password', 'password' => 'password-baru-123',
                'password_confirmation' => 'berbeda',
            ])->assertSessionHasErrorsIn('updatePassword', 'password');

            $this->from('/profile')->put('/password', [
                'current_password' => 'password', 'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])->assertRedirect('/profile')->assertSessionHas('status', 'password-updated');

            $this->assertTrue(Hash::check('password-baru-123', $user->refresh()->password_hash));
            $this->assertNotSame('password-baru-123', $user->password_hash);
        }
    }

    public function test_profile_photo_is_validated_and_private_to_the_signed_in_account(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'employer']);

        $this->actingAs($admin)->from('/admin/dashboard')->patch('/profile', [
            'name' => $admin->name,
            'email' => $admin->email,
            'profile_photo' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertRedirect('/admin/dashboard')->assertSessionHasNoErrors();

        $path = $admin->refresh()->profile_photo;
        Storage::disk('local')->assertExists($path);
        $this->get('/account/photo')->assertOk()->assertHeader('cache-control', 'no-store, private');
        $this->actingAs($other)->get('/account/photo')->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'jobseeker']))->get('/account/photo')->assertForbidden();

        $this->actingAs($admin)->from('/admin/dashboard')->patch('/profile', [
            'name' => $admin->name,
            'email' => $admin->email,
            'profile_photo' => UploadedFile::fake()->create('document.pdf', 1, 'application/pdf'),
        ])->assertSessionHasErrors('profile_photo');
        $this->assertSame($path, $admin->refresh()->profile_photo);
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
