<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Signing in and out of the console.
 *
 * The regressions these pin: the login form used to post to "/", which only
 * answers GET, so every sign-in ended on a 405 page; and signing out - or an
 * expired session - dropped the admin on the public home page instead of the
 * login form.
 */
class AdminLoginTest extends TestCase
{
    private const LOGIN = 'https://fati-api.alertaraqc.com/api/login';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function adminSession(): array
    {
        return [
            'admin_token' => 'test-admin-token',
            'admin_data' => ['role' => 'admin', 'email' => 'admin@example.test', 'first_name' => 'Ofelia'],
            'login_timestamp' => time(),
        ];
    }

    public function test_login_form_posts_to_the_login_route(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertSee('action="' . route('admin.login.post') . '"', false);
        $response->assertDontSee('action="/"', false);
    }

    public function test_posting_to_the_home_page_is_not_how_you_sign_in(): void
    {
        // The old form target. It never worked; it must not be what the page uses.
        $this->post('/')->assertStatus(405);
    }

    public function test_an_admin_signs_in_and_lands_on_the_dashboard(): void
    {
        Http::fake([self::LOGIN => Http::response(['data' => [
            'token' => 'api-token', 'role' => 'admin', 'user_id' => 1,
            'first_name' => 'Ofelia', 'last_name' => 'Store', 'email' => 'admin@example.test',
        ]])]);

        $response = $this->post('/admin/login', ['email' => 'admin@example.test', 'password' => 'secret']);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('admin_token', 'api-token');
        $response->assertSessionHas('admin_first_name', 'Ofelia');
    }

    public function test_a_student_account_is_refused_with_the_reason(): void
    {
        Http::fake([self::LOGIN => Http::response(['data' => ['token' => 't', 'role' => 'student']])]);

        $response = $this->from('/admin/login')->post('/admin/login', ['email' => 'juan@student.fatima.edu.ph', 'password' => 'secret']);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');
        $response->assertSessionMissing('admin_token');
    }

    public function test_wrong_password_shows_the_servers_message(): void
    {
        Http::fake([self::LOGIN => Http::response(['message' => 'These credentials do not match our records.'], 401)]);

        $response = $this->from('/admin/login')->post('/admin/login', ['email' => 'admin@example.test', 'password' => 'wrong']);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
    }

    public function test_signing_out_returns_to_the_login_page(): void
    {
        $response = $this->withSession($this->adminSession())->post('/logout');

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionMissing('admin_token');
        $response->assertSessionMissing('admin_data');
    }

    public function test_signing_out_with_a_get_also_works(): void
    {
        // A proxy that redirects http to https turns the sidebar's POST into a GET.
        $response = $this->withSession($this->adminSession())->get('/logout');

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionMissing('admin_token');
    }

    public function test_after_signing_out_the_dashboard_asks_to_sign_in(): void
    {
        $this->withSession($this->adminSession())->post('/logout');

        $this->get('/dashboard')->assertRedirect(route('admin.login'));
    }

    public function test_a_guest_is_sent_to_the_login_page_not_the_home_page(): void
    {
        $this->get('/dashboard')->assertRedirect('/admin/login');
        $this->get('/settings')->assertRedirect('/admin/login');
        $this->get('/counter')->assertRedirect('/admin/login');
    }

    public function test_an_expired_session_is_sent_to_the_login_page(): void
    {
        $session = $this->adminSession();
        $session['login_timestamp'] = time() - 90000;

        $response = $this->withSession($session)->get('/dashboard');

        $response->assertRedirect('/admin/login');
        $response->assertSessionHas('error');
        $response->assertSessionMissing('admin_token');
    }

    public function test_a_signed_in_admin_skips_the_login_form(): void
    {
        $this->withSession($this->adminSession())->get('/admin/login')->assertRedirect(route('admin.dashboard'));
    }
}
