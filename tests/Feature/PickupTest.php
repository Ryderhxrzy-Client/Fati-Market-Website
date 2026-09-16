<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Handing an order over from a phone.
 *
 * The twin of the item turnover. Completing an order credits the buyer's
 * reward points and is meant to be photographed, and the desk computer rarely
 * has a camera - so the console mints a one-order link, draws it as a QR, and
 * the phone that scans it finishes the job.
 */
class PickupTest extends TestCase
{
    private const API = 'https://fati-api.alertaraqc.com/api';
    private const REF = 'FMQR1.15.abcd';
    private const KEY = 'pickup-key-for-the-tests-0123456789';

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

    /** @param array<string, mixed> $overrides */
    private function order(array $overrides = []): array
    {
        return array_merge([
            'transaction_id' => 15,
            'receipt_no' => 'FM-000015',
            'qr_code' => self::REF,
            'buyer_id' => 9,
            'buyer_name' => 'Sheryl Cris Carigma',
            'buyer_email' => 'sheryl@student.fatima.edu.ph',
            'item_id' => 7,
            'item' => ['item_id' => 7, 'title' => 'Living in the IT Era', 'photos' => []],
            'amount_due' => '225.00',
            'points_used' => 5,
            'points_discount_amount' => '25.00',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'status' => 'ready_for_pickup',
            'reward_points_to_credit' => 2,
        ], $overrides);
    }

    private function fakeApi(array $overrides = []): void
    {
        Http::fake([
            self::API . '/admin/transactions/15' => Http::response(['data' => $this->order($overrides)]),
            self::API . '/admin/transactions/15/complete' => Http::response([
                'data' => $this->order(['status' => 'completed', 'payment_status' => 'verified']),
            ]),
        ]);
    }

    private function handoff(): void
    {
        Cache::put('fm-pickup-handoff:' . self::KEY, [
            'transaction_id' => 15,
            'ref' => self::REF,
            'token' => 'test-admin-token',
            'admin' => 'Ofelia Store',
            'expires_at' => now()->addMinutes(30)->toIso8601String(),
        ], now()->addMinutes(30));
    }

    public function test_the_console_mints_a_link_the_phone_can_open(): void
    {
        $this->fakeApi();

        $response = $this->withSession($this->session)
            ->postJson('/counter/pickup-handoff', ['transaction_id' => 15]);

        $response->assertOk()->assertJsonStructure(['url', 'expires_at', 'expires_in', 'order']);
        $this->assertStringContainsString('/pickup/' . self::REF . '?k=', $response->json('url'));

        $this->get($response->json('url'))
            ->assertOk()
            ->assertSee('Complete handover')
            ->assertSee('Living in the IT Era')
            ->assertSee('Sheryl Cris Carigma')
            ->assertSee('Handover photo');
    }

    public function test_minting_a_link_needs_an_admin_session(): void
    {
        $this->post('/counter/pickup-handoff', ['transaction_id' => 15])->assertRedirect('/admin/login');
        Http::assertNothingSent();
    }

    public function test_an_order_already_handed_over_is_not_offered_again(): void
    {
        $this->fakeApi(['status' => 'completed']);

        $this->withSession($this->session)
            ->postJson('/counter/pickup-handoff', ['transaction_id' => 15])
            ->assertStatus(422);
    }

    public function test_the_phone_page_refuses_a_key_it_never_minted(): void
    {
        $this->get('/pickup/' . self::REF . '?k=guessed')->assertStatus(410);
        $this->post('/pickup/' . self::REF, ['k' => 'guessed'])->assertStatus(410);
        Http::assertNothingSent();
    }

    public function test_the_photo_completes_the_order(): void
    {
        $this->fakeApi();
        $this->handoff();

        $this->post('/pickup/' . self::REF, [
            'k' => self::KEY,
            'handover_photo' => UploadedFile::fake()->image('handover.jpg'),
        ])->assertOk()->assertJsonPath('ok', true);

        Http::assertSent(fn ($request) => $request->url() === self::API . '/admin/transactions/15/complete'
            && str_contains($request->body(), 'name="handover_photo"'));
    }

    public function test_completing_without_a_photo_is_refused(): void
    {
        $this->fakeApi();
        $this->handoff();

        $this->postJson('/pickup/' . self::REF, ['k' => self::KEY])
            ->assertStatus(422)
            ->assertJsonValidationErrors('handover_photo');

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/complete'));
    }

    public function test_a_link_that_has_been_used_cannot_be_used_again(): void
    {
        $this->fakeApi();
        $this->handoff();

        $photo = fn () => ['k' => self::KEY, 'handover_photo' => UploadedFile::fake()->image('handover.jpg')];

        $this->post('/pickup/' . self::REF, $photo())->assertOk();
        $this->post('/pickup/' . self::REF, $photo())->assertStatus(410);
    }

    public function test_the_api_refusal_is_what_the_phone_is_told(): void
    {
        Http::fake([
            self::API . '/admin/transactions/15' => Http::response(['data' => $this->order()]),
            self::API . '/admin/transactions/15/complete' => Http::response([
                'message' => 'Payment must be verified before completing the order.',
            ], 409),
        ]);
        $this->handoff();

        $this->post('/pickup/' . self::REF, [
            'k' => self::KEY,
            'handover_photo' => UploadedFile::fake()->image('handover.jpg'),
        ])->assertStatus(409)->assertJsonPath('message', 'Payment must be verified before completing the order.');
    }

    public function test_the_console_offers_the_panel_wherever_orders_are_completed(): void
    {
        Http::fake(['fati-api.alertaraqc.com/api/*' => Http::response(['data' => []])]);

        foreach (['/conversations', '/transactions/history'] as $path) {
            $response = $this->withSession($this->session)->get($path);

            $response->assertOk();
            $response->assertSee('openPickup', false);
            $response->assertSee('Continue on phone');
        }
    }
}
