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

        $response->assertOk()->assertSee('Masuk Jobseeker');
        $this->get('/employer/login')->assertOk()->assertSee('Masuk sebagai Employer');
        $this->get('/admin/login')->assertOk()->assertSee('Admin Internal');
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

        $this->post('/logout')->assertRedirect('/');
        $this->withSession(['url.intended' => url("/jobs/{$jobId}/apply")])
            ->post('/register', [
                'name' => 'Pelamar Baru', 'email' => 'pelamar-baru@example.test',
                'password' => 'password123', 'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('jobs.show', $jobId))
            ->assertSessionMissing('url.intended');
    }

    public function test_signed_in_users_must_switch_accounts_before_opening_another_role_login(): void
    {
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($employer);
        $this->get('/admin/login')->assertRedirect(route('account.switch', ['role' => 'admin']));
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('account.switch', ['role' => 'admin']));
        $this->get('/account/switch/admin')->assertOk()
            ->assertSee($employer->email)
            ->assertSee('Semua tab memakai sesi yang sama.')
            ->assertDontSee('name="password"', false);
        $this->get('/employer/login')->assertRedirect(route('employer.dashboard'));
        $this->assertAuthenticatedAs($employer);

        $this->actingAs($admin);
        $this->get('/employer/login')->assertRedirect(route('account.switch', ['role' => 'employer']));
        $this->post('/employer/login', ['email' => $employer->email, 'password' => 'password'])
            ->assertRedirect(route('account.switch', ['role' => 'employer']));
        $this->get('/account/switch/employer')->assertOk()->assertSee($admin->email);
        $this->get('/admin/login')->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_switch_page_can_change_the_login_destination_without_changing_the_active_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);

        $this->actingAs($admin)->get('/login')
            ->assertRedirect(route('account.switch', ['role' => 'jobseeker']));
        $this->get('/account/switch/jobseeker')->assertOk()
            ->assertSee('href="'.route('account.switch', ['role' => 'employer']).'"', false)
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertSee('name="next" value="jobseeker"', false);

        $this->get('/dashboard')->assertRedirect(route('admin.dashboard'));
        $this->getJson('/api/me')->assertJsonPath('data.id', $admin->id);

        $this->get('/account/switch/employer')->assertOk()
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="next" value="employer"', false);
        $this->post('/logout', ['next' => 'employer'])
            ->assertRedirect(route('employer.login', absolute: false));
        $this->assertGuest();

        $this->post('/employer/login', ['email' => $employer->email, 'password' => 'password'])
            ->assertRedirect(route('employer.dashboard', absolute: false));
        $this->getJson('/api/me')->assertJsonPath('data.id', $employer->id);
    }

    public function test_jobseeker_can_switch_to_admin_and_employer_and_back(): void
    {
        $this->withCredentials();
        $jobseeker = User::factory()->create(['role' => 'jobseeker']);
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);

        $this->post('/login', ['email' => $jobseeker->email, 'password' => 'password'])
            ->assertRedirect(route('jobs.index', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get('/admin/login')->assertRedirect(route('account.switch', ['role' => 'admin']));
        $this->post('/logout', ['next' => 'admin'])->assertRedirect(route('admin.login', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->getJson('/api/me')->assertUnauthorized();

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->getJson('/api/me')->assertJsonPath('data.role', 'admin');
        $this->get('/login')->assertRedirect(route('account.switch', ['role' => 'jobseeker']));
        $this->post('/logout', ['next' => 'jobseeker'])->assertRedirect(route('login', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());

        $this->post('/login', ['email' => $jobseeker->email, 'password' => 'password'])
            ->assertRedirect(route('jobs.index', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get('/dashboard')->assertRedirect(route('jobs.index'));
        $this->get('/loker')->assertOk()->assertSee('data-role="jobseeker"', false);
        $this->getJson('/api/me')->assertJsonPath('data.id', $jobseeker->id);
        $this->get('/employer/login')->assertRedirect(route('account.switch', ['role' => 'employer']));
        $this->post('/logout', ['next' => 'employer'])->assertRedirect(route('employer.login', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());

        $this->post('/employer/login', ['email' => $employer->email, 'password' => 'password'])
            ->assertRedirect(route('employer.dashboard', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->getJson('/api/me')->assertJsonPath('data.role', 'employer');
        $this->get('/login')->assertRedirect(route('account.switch', ['role' => 'jobseeker']));
        $this->post('/logout', ['next' => 'jobseeker'])->assertRedirect(route('login', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->post('/login', ['email' => $jobseeker->email, 'password' => 'password'])
            ->assertRedirect(route('jobs.index', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->getJson('/api/me')->assertJsonPath('data.id', $jobseeker->id);
    }

    public function test_guest_opening_a_switch_url_reaches_the_requested_login(): void
    {
        $this->get('/account/switch/admin')->assertRedirect(route('admin.login'));
        $this->get('/account/switch/employer')->assertRedirect(route('employer.login'));
        $this->get('/account/switch/jobseeker')->assertRedirect(route('login'));
        $this->get('/account/switch/unknown')->assertNotFound();
    }

    public function test_switching_accounts_invalidates_the_old_session_and_refreshes_identity(): void
    {
        $this->withCredentials();
        $employer = User::factory()->create(['role' => 'employer']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post('/employer/login', ['email' => $employer->email, 'password' => 'password'])
            ->assertRedirect(route('employer.dashboard', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.role', 'employer');
        $this->get('/employer/dashboard')->assertRedirect(route('employer.setup'));
        $oldSessionId = session()->getId();
        $oldToken = session()->token();

        $this->post('/logout', ['next' => 'admin'])->assertRedirect(route('admin.login', absolute: false));
        $this->assertGuest();
        $this->assertNotSame($oldSessionId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->assertSame('', (string) session()->getHandler()->read($oldSessionId));
        $newSessionId = session()->getId();
        $this->withCookie(config('session.cookie'), $oldSessionId);
        $this->getJson('/api/me')->assertUnauthorized();
        $this->withCookie(config('session.cookie'), $newSessionId);
        $this->get('/employer/dashboard')->assertRedirect(route('employer.login'));

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));
        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.role', 'admin');
        $this->get('/admin/dashboard')->assertOk();
        $this->get('/employer/dashboard')->assertForbidden();
        $this->getJson('/api/me')->assertJsonPath('data.id', $admin->id);
    }

    public function test_switching_from_admin_to_employer_and_reloading_uses_the_current_backend_identity(): void
    {
        $this->withCredentials();
        $admin = User::factory()->create(['role' => 'admin']);
        $employer = User::factory()->create(['role' => 'employer']);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->getJson('/api/me')->assertJsonPath('data.role', 'admin');
        $this->get('/employer/login')->assertRedirect(route('account.switch', ['role' => 'employer']));
        $this->post('/logout', ['next' => 'employer'])->assertRedirect(route('employer.login', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->getJson('/api/me')->assertUnauthorized();

        $this->post('/employer/login', ['email' => $employer->email, 'password' => 'password'])
            ->assertRedirect(route('employer.dashboard', absolute: false));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->getJson('/api/me')->assertJsonPath('data.role', 'employer');
        $this->get('/admin/dashboard')->assertForbidden();
        $this->get('/employer/dashboard')->assertRedirect(route('employer.setup'));
        $this->getJson('/api/me')->assertJsonPath('data.id', $employer->id);
    }

    public function test_logout_destination_is_limited_to_known_login_routes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'employer']))
            ->post('/logout', ['next' => 'https://example.invalid'])
            ->assertRedirect('/');

        $this->assertGuest();
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
        $response->assertRedirect('/');
    }
}
