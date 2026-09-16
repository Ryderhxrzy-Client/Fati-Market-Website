<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Receiving an item at the counter, from the console and from a phone.
 *
 * The behaviour worth pinning down is the order: an item is verified with its
 * two photographs first, the seller is paid second, and only then does the
 * item land in the status the counter chose. Getting that sequence wrong is
 * how an item ends up on the catalog before anybody has actually handed it
 * over, so each test here asserts what was sent, not only what came back.
 */
class TurnoverTest extends TestCase
{
    private const API = 'https://fati-api.alertaraqc.com/api';
    private const REF = 'FMITEM1.7.aaaa';
    private const KEY = 'counter-key-for-the-tests-0123456789';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('t', 32))]);
        Http::preventStrayRequests();
    }

    private function adminSession(): array
    {
        return [
            'admin_token' => 'test-admin-token',
            'admin_data' => ['role' => 'admin', 'email' => 'admin@example.test'],
            'admin_first_name' => 'Ofelia',
            'admin_last_name' => 'Store',
        ];
    }

    /** @param array<string, mixed> $overrides */
    private function item(array $overrides = []): array
    {
        return array_merge([
            'item_id' => 7,
            'title' => 'Scientific calculator',
            'seller_email' => 'juan@student.test',
            'status' => 'pending',
            'seller_asking_price' => '300.00',
            'acquisition_price' => '180.00',
            'public_price' => null,
            'is_turnover_verified' => false,
            'photos' => [],
            'qr_code' => self::REF,
        ], $overrides);
    }

    private function handoff(): void
    {
        Cache::put('fm-turnover-handoff:' . self::KEY, [
            'item_id' => 7,
            'ref' => self::REF,
            'token' => 'test-admin-token',
            'admin' => 'Ofelia Store',
            'expires_at' => now()->addMinutes(30)->toIso8601String(),
        ], now()->addMinutes(30));
    }

    /** @param array<string, mixed> $lookup */
    private function fakeApi(array $lookup = []): void
    {
        Http::fake([
            self::API . '/items/7' => Http::response(['data' => $this->item($lookup)]),
            self::API . '/admin/items/7/verify-turnover' => Http::response([
                'data' => $this->item(['status' => 'acquired', 'is_turnover_verified' => true]),
            ]),
            self::API . '/admin/items/7/seller-payout' => Http::response([
                'data' => $this->item([
                    'status' => 'acquired',
                    'is_turnover_verified' => true,
                    'seller_payout_status' => 'paid',
                ]),
            ]),
            self::API . '/admin/items/7/publish' => Http::response([
                'data' => $this->item([
                    'status' => 'public',
                    'is_turnover_verified' => true,
                    'public_price' => '350.00',
                ]),
            ]),
            self::API . '/admin/items/7' => Http::response([
                'data' => $this->item(['status' => 'reserved', 'is_turnover_verified' => true]),
            ]),
        ]);
    }

    // ── The link the QR carries ──────────────────────────────────────────

    public function test_the_phone_page_refuses_a_key_it_never_minted(): void
    {
        $this->get('/turnover/' . self::REF . '?k=guessed')->assertStatus(410);
        $this->post('/turnover/' . self::REF, ['k' => 'guessed'])->assertStatus(410);
        Http::assertNothingSent();
    }

    public function test_a_key_minted_for_one_item_does_not_open_another(): void
    {
        $this->handoff();

        $this->get('/turnover/FMITEM1.8.bbbb?k=' . self::KEY)->assertStatus(410);
        Http::assertNothingSent();
    }

    public function test_the_console_mints_a_link_the_phone_can_open(): void
    {
        $this->fakeApi();

        $response = $this->withSession($this->adminSession())
            ->postJson('/counter/handoff', ['item_id' => 7]);

        $response->assertOk()->assertJsonStructure(['url', 'expires_at', 'expires_in', 'item']);

        $url = $response->json('url');
        $this->assertStringContainsString('/turnover/' . self::REF . '?k=', $url);

        // The phone opens exactly that URL, with no session of its own.
        $this->get($url)
            ->assertOk()
            ->assertSee('Scientific calculator')
            ->assertSee('Turnover proof')
            ->assertSee('Item turnover');
    }

    public function test_minting_a_link_needs_an_admin_session(): void
    {
        $this->post('/counter/handoff', ['item_id' => 7])->assertRedirect('/admin/login');
        $this->post('/counter/turnover/7')->assertRedirect('/admin/login');
        Http::assertNothingSent();
    }

    // ── The turnover itself ──────────────────────────────────────────────

    public function test_a_phone_turnover_verifies_then_pays_then_publishes(): void
    {
        $this->fakeApi();
        $this->handoff();

        $response = $this->post('/turnover/' . self::REF, [
            'k' => self::KEY,
            'turnover_photo' => UploadedFile::fake()->image('item.jpg'),
            'payout_photo' => UploadedFile::fake()->image('payout.jpg'),
            'public_price' => '350',
            'status' => 'public',
            'notes' => 'charger missing',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('item.status', 'public')
            ->assertJsonPath('warnings', []);

        Http::assertSentInOrder([
            fn ($request) => $request->url() === self::API . '/items/7' && $request->method() === 'GET',

            function ($request) {
                // Both photographs and the note ride along with the turnover.
                $body = $request->body();

                return $request->url() === self::API . '/admin/items/7/verify-turnover'
                    && str_contains($body, 'name="turnover_photo"')
                    && str_contains($body, 'name="payout_photo"')
                    && str_contains($body, 'charger missing');
            },

            fn ($request) => $request->url() === self::API . '/admin/items/7/seller-payout',

            fn ($request) => $request->url() === self::API . '/admin/items/7/publish'
                && $request['public_price'] === '350',
        ]);
    }

    public function test_a_turnover_without_both_photos_is_refused(): void
    {
        $this->fakeApi();
        $this->handoff();

        $response = $this->postJson('/turnover/' . self::REF, [
            'k' => self::KEY,
            'turnover_photo' => UploadedFile::fake()->image('item.jpg'),
        ]);

        $response->assertStatus(422)->assertJsonPath('errors.payout_photo.0', 'Photograph the seller being paid.');

        // The item was read, and nothing was written.
        Http::assertSentCount(1);
    }

    public function test_an_item_with_no_agreed_price_cannot_be_received_without_one(): void
    {
        $this->fakeApi(['acquisition_price' => null]);
        $this->handoff();

        $this->postJson('/turnover/' . self::REF, [
            'k' => self::KEY,
            'turnover_photo' => UploadedFile::fake()->image('item.jpg'),
            'payout_photo' => UploadedFile::fake()->image('payout.jpg'),
        ])->assertStatus(422)->assertJsonPath('errors.acquisition_price.0', 'Enter the agreed price.');

        Http::assertSentCount(1);
    }

    public function test_a_price_agreed_at_the_counter_travels_with_the_turnover(): void
    {
        $this->fakeApi(['acquisition_price' => null]);
        $this->handoff();

        $this->post('/turnover/' . self::REF, [
            'k' => self::KEY,
            'turnover_photo' => UploadedFile::fake()->image('item.jpg'),
            'payout_photo' => UploadedFile::fake()->image('payout.jpg'),
            'acquisition_price' => '175.50',
        ])->assertOk();

        Http::assertSent(fn ($request) => $request->url() === self::API . '/admin/items/7/verify-turnover'
            && str_contains($request->body(), '175.50'));
    }

    public function test_a_status_other_than_public_is_saved_with_the_price(): void
    {
        $this->fakeApi();
        $this->handoff();

        $this->post('/turnover/' . self::REF, [
            'k' => self::KEY,
            'turnover_photo' => UploadedFile::fake()->image('item.jpg'),
            'payout_photo' => UploadedFile::fake()->image('payout.jpg'),
            'public_price' => '350',
            'status' => 'reserved',
        ])->assertOk()->assertJsonPath('item.status', 'reserved');

        Http::assertSent(fn ($request) => $request->url() === self::API . '/admin/items/7'
            && $request->method() === 'PUT'
            && $request['status'] === 'reserved'
            && $request['public_price'] === '350');
    }

    public function test_an_item_already_in_the_store_is_repriced_without_photos(): void
    {
        $this->fakeApi(['status' => 'acquired', 'is_turnover_verified' => true]);
        $this->handoff();

        $this->postJson('/turnover/' . self::REF, [
            'k' => self::KEY,
            'public_price' => '350',
            'status' => 'public',
        ])->assertOk()->assertJsonPath('already_received', true);

        Http::assertSent(fn ($request) => $request->url() === self::API . '/admin/items/7/publish');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'verify-turnover'));
    }

    public function test_a_link_that_has_been_used_cannot_be_used_again(): void
    {
        $this->fakeApi();
        $this->handoff();

        $photos = fn () => [
            'k' => self::KEY,
            'turnover_photo' => UploadedFile::fake()->image('item.jpg'),
            'payout_photo' => UploadedFile::fake()->image('payout.jpg'),
        ];

        $this->post('/turnover/' . self::REF, $photos())->assertOk();

        // Whoever photographed the QR off the console screen gets nothing.
        $this->post('/turnover/' . self::REF, $photos())->assertStatus(410);
        $this->get('/turnover/' . self::REF . '?k=' . self::KEY)->assertStatus(410);
    }

    public function test_the_console_receives_an_item_on_this_computer(): void
    {
        $this->fakeApi();

        $this->withSession($this->adminSession())->post('/counter/turnover/7', [
            'turnover_photo' => UploadedFile::fake()->image('item.jpg'),
            'payout_photo' => UploadedFile::fake()->image('payout.jpg'),
        ])->assertOk()->assertJsonPath('ok', true);

        Http::assertSent(fn ($request) => $request->url() === self::API . '/admin/items/7/verify-turnover');
        Http::assertSent(fn ($request) => $request->url() === self::API . '/admin/items/7/seller-payout');
    }

    public function test_a_failed_turnover_says_so_and_stops(): void
    {
        Http::fake([
            self::API . '/items/7' => Http::response(['data' => $this->item()]),
            self::API . '/admin/items/7/verify-turnover' => Http::response(['message' => 'Already sold.'], 422),
        ]);
        $this->handoff();

        $this->postJson('/turnover/' . self::REF, [
            'k' => self::KEY,
            'turnover_photo' => UploadedFile::fake()->image('item.jpg'),
            'payout_photo' => UploadedFile::fake()->image('payout.jpg'),
        ])->assertStatus(422)->assertJsonPath('message', 'Already sold.');

        // No payout for a turnover that never happened.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'seller-payout'));
    }

    public function test_a_published_item_that_arrives_without_a_price_stays_acquired(): void
    {
        $this->fakeApi();
        $this->handoff();

        $response = $this->post('/turnover/' . self::REF, [
            'k' => self::KEY,
            'turnover_photo' => UploadedFile::fake()->image('item.jpg'),
            'payout_photo' => UploadedFile::fake()->image('payout.jpg'),
            'status' => 'public',
        ]);

        $response->assertOk()->assertJsonPath('item.status', 'acquired');
        $this->assertStringContainsString('needs a selling price', $response->json('warnings.0'));

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/publish'));
    }
}
