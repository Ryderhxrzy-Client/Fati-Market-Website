<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Changing the signed-in admin's password from the console.
 *
 * The console holds no passwords of its own: it hands the form to the API,
 * which is what checks the old one and ends the other sessions. What matters
 * here is that it forwards the whole form with the admin's token, and that a
 * refusal comes back to the page instead of disappearing.
 */
class ProfilePasswordTest extends TestCase
{
    private const ENDPOINT = 'https://fati-api.alertaraqc.com/api/account/password';

    private array $session = [
        'admin_token' => 'test-admin-token',
        'admin_data' => ['role' => 'admin', 'email' => 'admin@example.test'],
        'login_timestamp' => 0,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->session['login_timestamp'] = time();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('t', 32))]);
        Http::preventStrayRequests();
    }

    public function test_changing_a_password_needs_an_admin_session(): void
    {
        $this->post('/profile/password')->assertRedirect('/admin/login');
        Http::assertNothingSent();
    }

    public function test_the_form_is_forwarded_with_the_admin_token(): void
    {
        Http::fake([self::ENDPOINT => function ($request) {
            $this->assertSame('Bearer test-admin-token', $request->header('Authorization')[0]);
            $this->assertSame('Old!pass123', $request['current_password']);
            $this->assertSame('Str0ng!pass', $request['password']);
            $this->assertSame('Str0ng!pass', $request['password_confirmation']);

            return Http::response(['message' => 'Your password has been changed.']);
        }]);

        $this->withSession($this->session)
            ->post('/profile/password', [
                'current_password' => 'Old!pass123',
                'password' => 'Str0ng!pass',
                'password_confirmation' => 'Str0ng!pass',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHas('profile_success', 'Your password has been changed.');
    }

    public function test_a_wrong_current_password_comes_back_to_the_page(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'message' => 'That is not your current password.',
            'errors' => ['current_password' => ['That is not your current password.']],
        ], 422)]);

        $this->withSession($this->session)
            ->post('/profile/password', [
                'current_password' => 'wrong',
                'password' => 'Str0ng!pass',
                'password_confirmation' => 'Str0ng!pass',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('current_password');
    }

    public function test_mismatched_new_passwords_never_reach_the_api(): void
    {
        $this->withSession($this->session)
            ->post('/profile/password', [
                'current_password' => 'Old!pass123',
                'password' => 'Str0ng!pass',
                'password_confirmation' => 'something-else',
            ])
            ->assertSessionHasErrors('password');

        Http::assertNothingSent();
    }

    public function test_the_admin_can_correct_their_own_name(): void
    {
        Http::fake(['https://fati-api.alertaraqc.com/api/profile' => function ($request) {
            $this->assertSame('PUT', $request->method());
            $this->assertSame('Bearer test-admin-token', $request->header('Authorization')[0]);
            $this->assertSame('Ofelia', $request['first_name']);
            $this->assertSame('Store', $request['last_name']);

            return Http::response(['data' => ['first_name' => 'Ofelia', 'last_name' => 'Store']]);
        }]);

        $this->withSession($this->session)
            ->post('/profile', ['first_name' => 'Ofelia', 'last_name' => 'Store'])
            ->assertRedirect('/profile')
            ->assertSessionHas('profile_success');

        // The sidebar and the header read the session, not the API, so it has
        // to catch up or the old name stays on screen.
        $this->assertSame('Ofelia', session('admin_first_name'));
        $this->assertSame('Store', session('admin_last_name'));
    }

    public function test_a_blank_name_never_reaches_the_api(): void
    {
        $this->withSession($this->session)
            ->post('/profile', ['first_name' => '', 'last_name' => 'Store'])
            ->assertSessionHasErrors('first_name');

        Http::assertNothingSent();
    }

    public function test_changing_a_name_needs_an_admin_session(): void
    {
        $this->post('/profile', ['first_name' => 'A', 'last_name' => 'B'])->assertRedirect('/admin/login');
        Http::assertNothingSent();
    }

    public function test_the_profile_page_offers_the_name_form(): void
    {
        Http::fake(['fati-api.alertaraqc.com/api/*' => Http::response(['data' => []])]);

        $response = $this->withSession($this->session)->get('/profile');

        $response->assertOk();
        $response->assertSee('First name');
        $response->assertSee('Last name');
        $response->assertSee('Save name');
        $response->assertSee(route('admin.profile.update'), false);
    }

    public function test_the_profile_page_offers_the_form(): void
    {
        Http::fake(['fati-api.alertaraqc.com/api/*' => Http::response(['data' => []])]);

        $response = $this->withSession($this->session)->get('/profile');

        $response->assertOk();
        $response->assertSee('Change password');
        $response->assertSee('Current password');
        $response->assertSee(route('admin.profile.password'), false);
    }
}
