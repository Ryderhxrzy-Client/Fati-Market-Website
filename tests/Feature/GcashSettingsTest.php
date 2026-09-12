<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GcashSettingsTest extends TestCase
{
    private const ENDPOINT = 'https://fati-api.alertaraqc.com/api/admin/settings/gcash';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
        Http::preventStrayRequests();
    }

    private function adminSession(): array
    {
        return ['admin_token' => 'test-admin-token', 'admin_data' => ['role' => 'admin', 'email' => 'admin@example.test']];
    }

    public function test_settings_require_an_admin_session(): void
    {
        $this->get('/settings')->assertRedirect('/');
        $this->post('/settings/gcash')->assertRedirect('/');
        Http::assertNothingSent();
    }

    public function test_settings_render_current_backend_values(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['data' => [
            'account_name' => 'Store Owner', 'account_number' => '09171234567',
            'qr_image_url' => 'https://example.test/qr.png',
        ]])]);
        $this->withSession($this->adminSession())->get('/settings')->assertOk()
            ->assertSee('Store Owner')->assertSee('09171234567')->assertSee('https://example.test/qr.png')
            ->assertSee('Save GCash settings');
    }

    public function test_failed_load_does_not_offer_a_blank_save_form(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['message' => 'Settings unavailable'], 503)]);
        $this->withSession($this->adminSession())->get('/settings')->assertOk()
            ->assertSee('Settings unavailable')->assertDontSee('Save GCash settings');
    }

    public function test_name_number_and_qr_are_forwarded_with_admin_authentication(): void
    {
        Http::fake([self::ENDPOINT => function ($request) {
            // Inspect upload bytes while the request owns the open file stream.
            $this->assertStringContainsString('name="qr_image"', $request->body());
            $this->assertStringContainsString('New Owner', $request->body());
            $this->assertStringContainsString('+639171234567', $request->body());
            return Http::response(['message' => 'Saved']);
        }]);
        $this->withSession($this->adminSession())->post('/settings/gcash', [
            'account_name' => 'New Owner', 'account_number' => '+639171234567',
            'qr_image' => UploadedFile::fake()->image('gcash.png'),
        ])->assertRedirect('/settings')->assertSessionHas('gcash_success');
        Http::assertSent(fn ($request) => $request->url() === self::ENDPOINT
            && $request->hasHeader('Authorization', 'Bearer test-admin-token')
        );
    }

    public function test_backend_validation_errors_keep_entered_details(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'message' => 'Invalid QR', 'errors' => ['qr_image' => ['Invalid QR image']],
        ], 422)]);
        $this->withSession($this->adminSession())->from('/settings')->post('/settings/gcash', [
            'account_name' => 'New Owner', 'account_number' => '09171234567',
        ])->assertRedirect('/settings')->assertSessionHasErrors('qr_image')
            ->assertSessionHasInput('account_name', 'New Owner')->assertSessionMissing('gcash_success');
    }
}
