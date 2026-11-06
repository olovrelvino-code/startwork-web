<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertOk()->assertSee('Masuk Jobseeker')
            ->assertSee('Logo Start Work B transparan.png')
            ->assertSee('data-auth-error="password"', false)
            ->assertSee('Jelajahi Lowongan');
        $this->get('/employer/login')->assertOk()
            ->assertSee('Masuk sebagai Employer')
            ->assertSee('Logo Start Work B transparan.png')
            ->assertSee('auth-card', false)
            ->assertSee('data-auth-error="password"', false)
            ->assertSee('Jelajahi Lowongan');
        $this->get('/admin/login')->assertOk()->assertSee('Admin Internal');
    }

    public function test_employer_auth_pages_offer_only_email_and_password(): void
    {
        $this->get('/employer/login')->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('data-google-login', false);

        $this->get('/employer/register')->assertOk()
            ->assertSee('name="password_confirmation"', false)
            ->assertDontSee('Daftar dengan Google');

        $this->get('/auth/google/employer')->assertNotFound();
        $this->get('/auth/google/jobseeker')->assertRedirect();
    }

    public function test_jobseeker_registration_keeps_the_role_switch_and_registration_inputs_are_styled(): void
    {
        $this->get('/register')->assertOk()
            ->assertSee('data-auth-role="jobseeker"', false)
            ->assertSee('data-auth-role="employer"', false)
            ->assertSee('data-jobseeker-auth', false)
            ->assertSee('auth-register-input', false)
            ->assertSee('data-employer-auth', false);

        $this->get('/employer/register')->assertOk()
            ->assertSee('data-employer-form', false)
            ->assertSee('auth-register-input', false);
    }

    public function test_jobseeker_and_employer_login_validate_required_and_malformed_fields(): void
    {
        foreach (['/login', '/employer/login'] as $path) {
            $this->postJson($path, ['email' => '', 'password' => ''])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['email', 'password']);

            $this->postJson($path, ['email' => 'bukan-email', 'password' => 'password'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('email');
        }
    }

    public function test_jobseeker_and_employer_login_keep_invalid_credentials_generic(): void
    {
        foreach (['jobseeker' => '/login', 'employer' => '/employer/login'] as $role => $path) {
            $user = User::factory()->create(['role' => $role]);
            $unknownEmail = $this->postJson($path, ['email' => 'missing@example.test', 'password' => 'password']);
            $wrongPassword = $this->postJson($path, ['email' => $user->email, 'password' => 'wrong-password']);

            $unknownEmail->assertUnprocessable()->assertJsonValidationErrors('email');
            $wrongPassword->assertUnprocessable()->assertJsonValidationErrors('email');
            $this->assertSame($unknownEmail->json('errors.email'), $wrongPassword->json('errors.email'));
        }
    }

    public function test_each_login_route_accepts_only_its_role(): void
    {
        $routes = [
            'jobseeker' => ['/login', route('jobs.index', absolute: false)],
            'employer' => ['/employer/login', route('employer.dashboard', absolute: false)],
            'admin' => ['/admin/login', route('admin.dashboard', absolute: false)],
        ];

        foreach ($routes as $role => [$correctPath, $destination]) {
            $user = User::factory()->create(['role' => $role]);

            foreach ($routes as [$loginPath]) {
                $response = $this->post($loginPath, ['email' => $user->email, 'password' => 'password']);

                if ($loginPath === $correctPath) {
                    $response->assertRedirect($destination);
                    $this->assertAuthenticatedAs($user);
                    $this->post('/logout');
                } else {
                    $response->assertSessionHasErrors('email');
                    $this->assertGuest();
                }
            }
        }
    }

    public function test_login_discards_an_intended_page_from_another_role(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);

        $this->withSession(['url.intended' => url('/admin/dashboard')])
            ->post('/employer/login', ['email' => $employer->email, 'password' => 'password'])
            ->assertRedirect(route('employer.dashboard'));
    }

    public function test_jobseeker_login_opens_jobs_even_with_an_intended_page(): void
    {
        $jobseeker = User::factory()->create(['role' => 'jobseeker']);

        $this->withSession(['url.intended' => url('/profil')])
            ->post('/login', ['email' => $jobseeker->email, 'password' => 'password'])
            ->assertRedirect(route('jobs.index', absolute: false))
            ->assertSessionMissing('url.intended');

        $this->get('/dashboard')->assertRedirect(route('jobs.index'));
        $this->get('/loker')->assertOk();
        $this->get('/profil')->assertOk();
        $this->get('/profil/lamaran')->assertOk();
        $this->get('/login')->assertRedirect(route('jobs.index'));
    }

    public function test_jobseeker_login_and_registration_resume_only_a_job_apply_intent(): void
    {
        $jobId = (string) Str::uuid();
        $jobseeker = User::factory()->create(['role' => 'jobseeker']);
        $this->withSession(['url.intended' => url("/jobs/{$jobId}/apply")])
            ->post('/login', ['email' => $jobseeker->email, 'password' => 'password'])
            ->assertRedirect(route('jobs.show', $jobId))
            ->assertSessionMissing('url.intended');

        $this->post('/logout')->assertRedirect(route('login', absolute: false));
        $this->withSession(['url.intended' => url("/jobs/{$jobId}/apply")])
            ->post('/register', [
                'name' => 'Pelamar Baru', 'email' => 'pelamar-baru@example.test',
                'password' => 'password123', 'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('jobs.show', $jobId))
            ->assertSessionMissing('url.intended');
    }

    public function test_signed_in_users_cannot_open_another_roles_login_or_replace_their_session(): void
    {
        $users = [];
        foreach (['jobseeker', 'employer', 'admin'] as $role) {
            $users[$role] = User::factory()->create(['role' => $role]);
        }

        foreach (['jobseeker' => '/login', 'employer' => '/employer/login', 'admin' => '/admin/login'] as $role => $path) {
            $this->actingAs($users[$role]);

            foreach (['jobseeker' => '/login', 'employer' => '/employer/login', 'admin' => '/admin/login'] as $targetRole => $targetPath) {
                if ($role === $targetRole) {
                    $this->get($targetPath)->assertRedirect(
                        route($role === 'jobseeker' ? 'jobs.index' : $role.'.dashboard')
                    );

                    continue;
                }

                $this->get($targetPath)->assertForbidden();
                $this->post($targetPath, ['email' => $users[$targetRole]->email, 'password' => 'password'])->assertForbidden();
                $this->assertAuthenticatedAs($users[$role]);
            }
        }
    }

    public function test_account_switch_urls_are_gone_for_guests_and_authenticated_users(): void
    {
        $this->get('/account/switch/admin')->assertNotFound();
        $this->get('/account/switch/employer')->assertNotFound();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/account/switch/jobseeker')->assertNotFound();
    }

    public function test_signed_in_users_cannot_open_another_roles_registration_or_google_login(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $jobseeker = User::factory()->create(['role' => 'jobseeker']);

        $this->actingAs($employer)->get('/register')->assertForbidden();
        $this->post('/register', [])->assertForbidden();
        $this->get('/auth/google/jobseeker')->assertForbidden();
        $this->assertAuthenticatedAs($employer);

        $this->actingAs($jobseeker)->get('/employer/register')->assertForbidden();
        $this->assertAuthenticatedAs($jobseeker);
    }

    public function test_guests_reach_the_matching_login_for_protected_roles(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
        $this->get('/employer/dashboard')->assertRedirect(route('employer.login'));
        $this->get('/profil')->assertRedirect(route('login'));
    }

    public function test_logout_invalidates_the_old_session_and_login_refreshes_identity_across_tabs(): void
    {
        $this->withCredentials();
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $oldSessionId = session()->getId();
        $this->post('/employer/login', ['email' => $employer->email, 'password' => 'password'])
            ->assertRedirect(route('employer.dashboard', absolute: false));
        $this->assertNotSame($oldSessionId, session()->getId());
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get('/admin/dashboard')->assertForbidden();
        $this->getJson('/api/me')->assertJsonPath('data.role', 'employer');
        $employerSessionId = session()->getId();
        $oldToken = session()->token();

        $this->post('/logout')->assertRedirect(route('employer.login', absolute: false));
        $this->assertGuest();
        $this->assertNotSame($employerSessionId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->assertSame('', (string) session()->getHandler()->read($employerSessionId));

        $this->withCookie(config('session.cookie'), $employerSessionId);
        $this->getJson('/api/me')->assertUnauthorized();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));
        $this->get('/employer/dashboard')->assertForbidden();
        $this->getJson('/api/me')->assertJsonPath('data.id', $admin->id);
    }

    public function test_logout_returns_to_the_current_roles_login_and_ignores_next_input(): void
    {
        foreach (['jobseeker' => 'login', 'employer' => 'employer.login', 'admin' => 'admin.login'] as $role => $loginRoute) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->post('/logout', ['next' => 'https://example.invalid'])
                ->assertRedirect(route($loginRoute, absolute: false));

            $this->assertGuest();
        }
    }

    public function test_dashboard_and_sensitive_api_routes_reject_other_roles(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $jobseeker = User::factory()->create(['role' => 'jobseeker']);

        $this->actingAs($employer)->get('/admin/dashboard')->assertForbidden();
        $this->get('/admin')->assertForbidden();
        $this->getJson('/api/admin/companies')->assertForbidden()
            ->assertHeader('content-type', 'application/json');
        $this->postJson('/api/admin/companies', ['name' => 'Tidak sah'])->assertForbidden();

        $this->actingAs($admin)->get('/employer/dashboard')->assertForbidden();
        $this->get('/employer')->assertForbidden();
        $this->getJson('/api/employer/company')->assertForbidden();
        $this->postJson('/api/employer/company', ['name' => 'Tidak sah'])->assertForbidden();

        $this->actingAs($jobseeker)->get('/admin/dashboard')->assertForbidden();
        $this->get('/admin')->assertForbidden();
        $this->get('/employer/dashboard')->assertForbidden();
        $this->getJson('/api/admin/companies')->assertForbidden();
        $this->getJson('/api/employer/company')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'unknown']))
            ->get('/dashboard')->assertForbidden();
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('jobs.index', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }
}
