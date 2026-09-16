<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The shelf between the counter and the catalog.
 *
 * The page exists to put an item on sale, and that was the one thing it could
 * not do. It offered "Send points", left from when sellers were paid in wallet
 * points, and a message box that wrote away from the seller's thread.
 */
class AcquiredItemsTest extends TestCase
{
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

        Http::fake([
            'fati-api.alertaraqc.com/api/admin/items*' => Http::response(['data' => [
                [
                    'item_id' => 21, 'seller_id' => 2, 'seller_email' => 'juan@student.fatima.edu.ph',
                    'title' => 'Scientific calculator', 'status' => 'acquired', 'photos' => [],
                    'seller_asking_price' => '300.00', 'acquisition_price' => '180.00',
                    'public_price' => null, 'seller_payout_status' => 'paid', 'seller_payout_amount' => '180.00',
                    'is_turnover_verified' => true, 'acquired_at' => '2026-09-15T10:00:00Z',
                    'turnover_photo' => 'https://cdn.test/received-21.jpg',
                    'seller_payout_photo' => 'https://cdn.test/paid-21.jpg',
                ],
                [
                    'item_id' => 22, 'seller_id' => 3, 'seller_email' => 'maria@student.fatima.edu.ph',
                    'title' => 'Anatomy book', 'status' => 'acquired', 'photos' => [],
                    'seller_asking_price' => '200.00', 'acquisition_price' => '150.00',
                    'public_price' => '260.00', 'seller_payout_status' => 'unpaid',
                    'is_turnover_verified' => true, 'acquired_at' => '2026-09-16T10:00:00Z',
                ],
                [
                    'item_id' => 23, 'seller_id' => 4, 'seller_email' => 'pedro@student.fatima.edu.ph',
                    'title' => 'Nursing scrub suit', 'status' => 'public', 'photos' => [],
                    'seller_asking_price' => '400.00', 'acquisition_price' => '300.00',
                    'public_price' => '450.00', 'seller_payout_status' => 'paid',
                    'is_turnover_verified' => true, 'acquired_at' => '2026-09-16T11:00:00Z',
                ],
            ]]),
            'fati-api.alertaraqc.com/api/*' => Http::response(['data' => []]),
        ]);
    }

    private function page()
    {
        return $this->withSession($this->session)->get('/inventory/acquired-items');
    }

    public function test_a_row_shows_what_was_paid_and_what_it_sells_for(): void
    {
        $response = $this->page();

        $response->assertOk();
        $response->assertSee('₱180.00');
        $response->assertSee('₱260.00');
        $response->assertSee('markup ₱110.00');
        $response->assertSee('Not priced yet');
        $response->assertSee('Seller paid');
        $response->assertSee('Seller unpaid');
    }

    public function test_an_item_in_the_store_can_be_published_from_its_row(): void
    {
        $response = $this->page();

        $response->assertSee('Publish');
        $response->assertSee('askPublish(21', false);
        $response->assertSee('Public selling price (₱)');

        // The preview is the server's, so what is promised is what is stored.
        $response->assertSee('publish-preview', false);
        $response->assertSee('Buyer earns', false);
    }

    public function test_an_item_already_on_the_catalog_is_not_offered_again(): void
    {
        $this->page()->assertDontSee('askPublish(23', false);
    }

    public function test_the_seller_button_opens_their_conversation(): void
    {
        $response = $this->page();

        $response->assertSee(route('admin.conversations') . '?thread=21-2', false);
        $response->assertSee('Open conversation');
        $response->assertDontSee('Message Seller');
        $response->assertDontSee('messageContent', false);
    }

    public function test_sending_points_to_a_seller_is_gone(): void
    {
        $response = $this->page();

        // Sellers are paid cash at the counter; wallet points are a buyer's
        // reward and never a payout.
        $response->assertDontSee('Send Points');
        $response->assertDontSee('send-points-btn', false);
        $response->assertDontSee('sendPointsModal', false);
    }

    public function test_a_paid_seller_row_carries_the_counter_photographs(): void
    {
        $response = $this->page();

        // The row claims the seller was paid; this is what that rests on.
        $response->assertSee('Payment proof');
        $response->assertSee('https://cdn.test/paid-21.jpg', false);
        $response->assertSee('https://cdn.test/received-21.jpg', false);
        $response->assertSee('The seller being paid', false);
    }

    public function test_a_row_with_no_photographs_offers_nothing_to_open(): void
    {
        $response = $this->page();

        // Item 22 was received without proof, so there is no button for it.
        $response->assertDontSee('data-title="Anatomy book" data-item-photo', false);
    }

    public function test_publishing_can_fix_the_listing_on_the_way_out(): void
    {
        $response = $this->page();

        // Folded away, because most items go up as they are.
        $response->assertSee('Edit information');
        $response->assertSee('Optional. The title, the description and the photos buyers will see.');
        $response->assertSee('togglePublishEdit', false);
        $response->assertSee('id="publishTitle"', false);
        $response->assertSee('id="publishDescription"', false);

        // Choosing a photo adds it: no second button to forget.
        $response->assertSee('Choosing a photo adds it straight away.');
        $response->assertDontSee('>Add photos<', false);

        // The edits are saved before the item goes live, not after.
        $response->assertSee('savePublishDetails', false);
    }

    public function test_the_full_item_editor_is_on_the_page(): void
    {
        $response = $this->page();

        $response->assertSee('openItemEdit', false);
        $response->assertSee('itemEditModal', false);
        $response->assertSee('Manage photos');

        // The old two-field box is gone in favour of it.
        $response->assertDontSee('editMarkupPoints', false);
    }
}
