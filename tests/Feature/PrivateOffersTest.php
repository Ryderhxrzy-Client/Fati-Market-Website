<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The offers page as a decision queue.
 *
 * What matters is that a row says where its offer stands - nobody has looked
 * at it, a price was agreed, the item is in the store - and that it carries
 * the move that comes next. The page used to show none of that, and its
 * "Message seller" box fired a message away from the thread it belonged to.
 */
class PrivateOffersTest extends TestCase
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
                    'item_id' => 11, 'seller_id' => 2, 'seller_email' => 'juan@student.fatima.edu.ph',
                    'title' => 'Nursing scrub suit', 'status' => 'pending', 'photos' => [],
                    'seller_asking_price' => '300.00', 'acquisition_price' => null,
                    'public_price' => null, 'is_turnover_verified' => false,
                    'created_at' => '2026-09-14T10:00:00Z',
                ],
                [
                    'item_id' => 12, 'seller_id' => 3, 'seller_email' => 'maria@student.fatima.edu.ph',
                    'title' => 'Scientific calculator', 'status' => 'pending', 'photos' => [],
                    'seller_asking_price' => '300.00', 'acquisition_price' => '180.00',
                    'public_price' => null, 'is_turnover_verified' => false,
                    'created_at' => '2026-09-15T10:00:00Z',
                ],
                [
                    'item_id' => 13, 'seller_id' => 4, 'seller_email' => 'pedro@student.fatima.edu.ph',
                    'title' => 'Anatomy book', 'status' => 'acquired', 'photos' => [],
                    'seller_asking_price' => '200.00', 'acquisition_price' => '150.00',
                    'public_price' => null, 'is_turnover_verified' => true,
                    'created_at' => '2026-09-16T10:00:00Z',
                ],
            ]]),
            'fati-api.alertaraqc.com/api/*' => Http::response(['data' => []]),
        ]);
    }

    private function page()
    {
        return $this->withSession($this->session)->get('/inventory/private-offers');
    }

    public function test_every_row_says_where_its_offer_stands(): void
    {
        $response = $this->page();

        $response->assertOk();
        $response->assertSee('Waiting for review');
        $response->assertSee('Offer accepted');
        $response->assertSee('Agreed at ₱180.00');
        $response->assertSee('Received');
    }

    public function test_an_unanswered_offer_offers_to_accept_it(): void
    {
        $response = $this->page();

        $response->assertSee('Approve');
        $response->assertSee('approveOffer(11', false);
        $response->assertSee('Acquisition price (₱)');
    }

    public function test_an_accepted_offer_offers_the_counter_panel(): void
    {
        $response = $this->page();

        // The same panel the chat opens: phone handoff, or the proof here.
        $response->assertSee('Mark acquired');
        $response->assertSee('openTurnover(12', false);
        $response->assertSee('Continue on phone');
        $response->assertSee('Do it here');
    }

    public function test_an_item_already_in_the_store_is_not_asked_for_again(): void
    {
        $response = $this->page();

        $response->assertDontSee('approveOffer(13', false);
        $response->assertDontSee('openTurnover(13', false);
    }

    public function test_an_offer_the_store_has_not_taken_in_can_be_deleted(): void
    {
        $response = $this->page();

        // The unanswered offer and the accepted-but-not-received one.
        $response->assertSee('askDeleteOffer(11', false);
        $response->assertSee('askDeleteOffer(12', false);
        $response->assertSee('Delete offer');
    }

    public function test_the_delete_asks_first_and_says_what_goes_with_it(): void
    {
        $response = $this->page();

        $response->assertSee('Delete this offer?');
        $response->assertSee('The conversation about this item goes too, for both of you.');
        $response->assertSee('This cannot be undone.');
        $response->assertSee('Keep it');
    }

    public function test_an_item_already_in_the_store_has_no_delete_button(): void
    {
        $response = $this->page();

        $response->assertDontSee('askDeleteOffer(13', false);
    }

    public function test_the_seller_button_opens_their_conversation(): void
    {
        $response = $this->page();

        $response->assertSee(route('admin.conversations') . '?thread=11-2', false);
        $response->assertSee('Open conversation');

        // The box that sent a message away from the thread is gone.
        $response->assertDontSee('Message Seller');
        $response->assertDontSee('messageContent', false);
    }
}
