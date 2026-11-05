<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google', [
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'redirect' => route('google.callback'),
        ]);
    }

    public function test_only_jobseekers_redirect_to_google_with_state_and_role_context(): void
    {
        $response = $this->get(route('google.redirect', 'jobseeker'));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/', $response->headers->get('Location'));
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('select_account', $query['prompt'] ?? null);
        $response->assertSessionHas('google_oauth_role', 'jobseeker');
        $response->assertSessionHas('state');

        $this->get('/auth/google/employer')->assertNotFound();
        $this->get('/auth/google/admin')->assertNotFound();
    }

    public function test_google_flow_starts_on_the_callback_origin_so_state_survives(): void
    {
        config()->set('services.google.redirect', 'http://localhost:8000/auth/google/callback');

        $this->get('http://startwork.test/auth/google/jobseeker')
            ->assertRedirect('http://localhost:8000/auth/google/jobseeker')
            ->assertSessionMissing('google_oauth_role');

        $this->get('http://localhost:8000/auth/google/jobseeker')
            ->assertSessionHas('google_oauth_role', 'jobseeker')
            ->assertSessionHas('state');
    }

    public function test_google_callback_keeps_registration_on_callback_origin(): void
    {
        config()->set('services.google.redirect', 'http://localhost:8000/auth/google/callback');
        $this->mockGoogleUser('localhost-user', 'localhost@example.test', true, 'Nama Lokal');

        $this->withSession($this->oauthSession('jobseeker'))
            ->get('http://localhost:8000/auth/google/callback?code=code&state=state')
            ->assertRedirect('http://localhost:8000/login?mode=register');
    }

    public function test_both_login_pages_keep_the_role_switch(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('data-auth-role="jobseeker"', false)
            ->assertSee('data-auth-role="employer"', false)
            ->assertSee(route('login', ['mode' => 'register']), false);
        $this->get('/employer/login')->assertOk()
            ->assertSee('data-role-switch', false)
            ->assertSee('data-auth-role="jobseeker"', false)
            ->assertSee('data-auth-role="employer"', false)
            ->assertSee(route('employer.login', ['mode' => 'register']), false);
        $this->get('/register')->assertOk()->assertSee('data-role-switch', false);
        $this->get('/employer/register')->assertOk()->assertSee('data-role-switch', false);
    }

    public function test_missing_google_configuration_returns_to_login_with_feedback(): void
    {
        config()->set('services.google.client_id', null);

        $this->get(route('google.redirect', 'jobseeker'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }

    public function test_callback_rejects_missing_context_invalid_state_and_cancel(): void
    {
        $this->get(route('google.callback').'?code=code&state=state')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->withSession($this->oauthSession('jobseeker') + ['state' => 'expected'])
            ->get(route('google.callback').'?code=code&state=incorrect')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->withSession($this->oauthSession('employer'))
            ->get(route('google.callback').'?error=access_denied')
            ->assertRedirect(route('employer.login'));

        $this->assertGuest();
    }

    public function test_unregistered_verified_google_email_stays_on_auth_page_without_creating_a_user(): void
    {
        $this->mockGoogleUser('new-jobseeker', 'jobseeker@example.test', true, 'Nama Google');

        $this->withSession($this->oauthSession('jobseeker'))
            ->get(route('google.callback').'?code=code&state=state')
            ->assertRedirect(route('login', ['mode' => 'register']))
            ->assertSessionHas('google_oauth_registration')
            ->assertSessionHas('status');
        $this->get(route('login', ['mode' => 'register']))->assertOk()
            ->assertSee('data-role-switch', false)
            ->assertSee('Nama Google')->assertSee('jobseeker@example.test')
            ->assertSee('name="registration_method" value="google"', false)
            ->assertDontSee('name="password"', false);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_existing_email_requires_correct_local_password_before_google_is_linked(): void
    {
        $user = User::factory()->create(['role' => 'jobseeker', 'email' => 'existing@example.test']);
        $this->mockGoogleUser('google-existing', $user->email);

        $this->withSession($this->oauthSession('jobseeker'))
            ->get(route('google.callback').'?code=code&state=state')
            ->assertRedirect(route('login'))
            ->assertSessionHas('google_oauth_pending');

        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertNull($user->fresh()->google_id);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('jobs.index'))
            ->assertSessionMissing('google_oauth_pending');
        $this->assertSame('google-existing', $user->fresh()->google_id);

        $this->post('/logout');
        $this->mockGoogleUser('google-existing', $user->email);
        $this->withSession($this->oauthSession('jobseeker'))
            ->get(route('google.callback').'?code=code&state=state')
            ->assertRedirect(route('jobs.index'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_employer_google_callback_cannot_link_an_account(): void
    {
        $user = User::factory()->create(['role' => 'employer', 'email' => 'employer@example.test', 'phone' => '08123456789']);
        $this->withSession($this->oauthSession('employer'))
            ->get(route('google.callback').'?code=code&state=state')
            ->assertRedirect(route('employer.login'));

        $this->assertNull($user->fresh()->google_id);

        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/employer/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('employer.dashboard'));
        $this->assertNull($user->fresh()->google_id);
        $this->get(route('employer.setup'))->assertOk()->assertSee('Langkah 2 dari 2')
            ->assertSee('data-role-switch', false);
    }

    public function test_expired_google_link_does_not_change_a_local_account(): void
    {
        $user = User::factory()->create(['role' => 'jobseeker']);

        $this->withSession(['google_oauth_pending' => [
            'id' => 'expired-google',
            'email' => $user->email,
            'role' => 'jobseeker',
            'expires_at' => now()->subSecond()->timestamp,
        ]])->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('jobs.index'));

        $this->assertNull($user->fresh()->google_id);
    }

    public function test_linked_google_accounts_log_into_only_the_matching_role(): void
    {
        $user = User::factory()->create(['role' => 'jobseeker', 'google_id' => 'google-jobseeker']);
        $this->mockGoogleUser('google-jobseeker', $user->email);

        $this->withSession($this->oauthSession('jobseeker'))
            ->get(route('google.callback').'?code=code&state=state')
            ->assertRedirect(route('jobs.index'));
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');

        $this->withSession($this->oauthSession('employer'))
            ->get(route('google.callback').'?code=code&state=state')
            ->assertRedirect(route('employer.login'));
        $this->assertGuest();
    }

    public function test_google_jobseeker_registration_uses_verified_identity_without_password_and_logs_in_next_time(): void
    {
        $this->mockGoogleUser('new-jobseeker-id', 'new-jobseeker@example.test', true, 'Nama Dari Google');
        $this->withSession($this->oauthSession('jobseeker'))
            ->get(route('google.callback').'?code=code&state=state')
            ->assertRedirect(route('login', ['mode' => 'register']));

        $this->post('/register', [
            'registration_method' => 'google', 'name' => 'Nama Dari Google',
            'email' => 'new-jobseeker@example.test',
        ])->assertRedirect(route('jobs.index'));
        $user = User::where('email', 'new-jobseeker@example.test')->firstOrFail();
        $this->assertSame('jobseeker', $user->role);
        $this->assertSame('new-jobseeker-id', $user->google_id);
        $this->assertNull($user->password_hash);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->mockGoogleUser('new-jobseeker-id', $user->email);
        $this->withSession($this->oauthSession('jobseeker'))
            ->get(route('google.callback').'?code=code&state=state')
            ->assertRedirect(route('jobs.index'));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_incomplete_linked_employer_completes_only_missing_account_data_before_step_two(): void
    {
        $user = User::factory()->create(['role' => 'employer', 'phone' => null, 'google_id' => 'legacy-employer']);
        $this->actingAs($user)->get(route('employer.setup'))->assertOk()
            ->assertSee('Langkah 1 dari 2')->assertSee('data-role-switch', false)
            ->assertDontSee('name="password"', false);
        $this->get(route('employer.setup', ['step' => 2]))->assertOk()->assertSee('Langkah 1 dari 2');
        $this->patchJson(route('employer.setup.account'), [
            'full_name' => 'Nama Lain', 'phone' => '08123456789', 'email' => 'other@example.test',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertNull($user->fresh()->phone);
        $this->patchJson(route('employer.setup.account'), [
            'full_name' => $user->full_name, 'phone' => '08123456789', 'email' => $user->email,
        ])->assertOk();
        $this->assertSame('08123456789', $user->fresh()->phone);
        $this->get(route('employer.setup'))->assertOk()->assertSee('Langkah 2 dari 2');
        $this->actingAs(User::factory()->create(['role' => 'jobseeker']))
            ->patchJson(route('employer.setup.account'), [
                'full_name' => 'Disusupi', 'phone' => '08111111111', 'email' => $user->email,
            ])->assertForbidden();
        $this->assertSame('08123456789', $user->fresh()->phone);
    }

    public function test_google_registration_rejects_expired_context_tampered_email_and_linked_google_id(): void
    {
        $pending = ['id' => 'new-google', 'email' => 'new@example.test', 'name' => 'Nama Google',
            'role' => 'jobseeker', 'expires_at' => now()->addMinutes(10)->timestamp];
        $this->withSession(['google_oauth_registration' => $pending])
            ->post('/register', ['registration_method' => 'google', 'name' => 'Nama Google', 'email' => 'other@example.test'])
            ->assertSessionHasErrors('email');
        $this->withSession(['google_oauth_registration' => [...$pending, 'expires_at' => now()->subSecond()->timestamp]])
            ->post('/register', ['registration_method' => 'google', 'name' => 'Nama Google', 'email' => 'new@example.test'])
            ->assertSessionHasErrors('email');

        $this->withSession(['google_oauth_registration' => [...$pending, 'role' => 'employer']])
            ->post('/register', ['registration_method' => 'google', 'name' => 'Nama Google', 'email' => 'new@example.test'])
            ->assertSessionHasErrors('email');

        User::factory()->create(['role' => 'employer', 'google_id' => 'taken-google']);
        $this->withSession(['google_oauth_registration' => [
            'id' => 'taken-google', 'email' => 'employer-new@example.test', 'name' => 'Pemilik',
            'role' => 'employer', 'expires_at' => now()->addMinutes(10)->timestamp,
        ]])->postJson('/api/employer/register', [
            'registration_method' => 'google', 'full_name' => 'Pemilik',
            'phone' => '08123456789', 'email' => 'employer-new@example.test',
        ])->assertStatus(409);
        $this->assertDatabaseMissing('users', ['email' => 'employer-new@example.test']);
    }

    public function test_callback_rejects_unverified_email_and_different_google_identity_for_existing_email(): void
    {
        $user = User::factory()->create(['role' => 'jobseeker', 'email' => 'jobseeker@example.test', 'google_id' => 'original-google']);
        $this->mockGoogleUser('new-google', $user->email, false);
        $this->withSession($this->oauthSession('jobseeker'))
            ->get(route('google.callback').'?code=code&state=state')
            ->assertSessionHasErrors('google');

        $this->mockGoogleUser('new-google', $user->email);
        $this->withSession($this->oauthSession('jobseeker'))
            ->get(route('google.callback').'?code=code&state=state')
            ->assertSessionHasErrors('google');

        $this->assertSame('original-google', $user->fresh()->google_id);
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_google_email_matching_another_role_does_not_link_or_log_in(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@example.test']);
        $this->mockGoogleUser('google-admin', $admin->email);

        $this->withSession($this->oauthSession('jobseeker'))
            ->get(route('google.callback').'?code=code&state=state')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertNull($admin->fresh()->google_id);
        $this->assertGuest();
    }

    /** @return array{google_oauth_role: string, google_oauth_started_at: int} */
    private function oauthSession(string $role): array
    {
        return ['google_oauth_role' => $role, 'google_oauth_started_at' => now()->timestamp];
    }

    private function mockGoogleUser(string $id, string $email, bool $verified = true, ?string $name = null): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn(GoogleUser::fake([
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'email_verified' => $verified,
        ]));
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
    }
}
