<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The sold list, read as a record of sales rather than of listings.
 *
 * A sold row used to show what the store had hoped to charge and nothing
 * about what actually happened: no buyer, no payment method, no amount, and
 * no sign of the points the buyer earned back. It also offered an Edit button
 * for an item that is history.
 */
class SoldItemsTest extends TestCase
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
                    'item_id' => 31, 'seller_id' => 2, 'seller_email' => 'juan@student.fatima.edu.ph',
                    'title' => 'Scientific calculator', 'status' => 'sold',
                    'photos' => ['https://cdn.test/one.jpg', 'https://cdn.test/two.jpg'],
                    'seller_asking_price' => '300.00', 'acquisition_price' => '180.00',
                    'public_price' => '250.00', 'markup' => '70.00',
                    'created_at' => '2026-09-10T10:00:00Z',
                    'sale' => [
                        'transaction_id' => 15,
                        'receipt_no' => 'FM-000015',
                        'buyer_id' => 9,
                        'buyer_email' => 'sheryl@student.fatima.edu.ph',
                        'buyer_name' => 'Sheryl Cris Carigma',
                        'payment_method' => 'gcash',
                        'payment_status' => 'verified',
                        'subtotal' => '250.00',
                        'amount_due' => '225.00',
                        'points_used' => 5,
                        'points_discount_amount' => '25.00',
                        'reward_points_earned' => 2,
                        'completed_at' => '2026-09-16 14:05:00',
                    ],
                ],
                [
                    'item_id' => 32, 'seller_id' => 3, 'seller_email' => 'maria@student.fatima.edu.ph',
                    'title' => 'Anatomy book', 'status' => 'sold', 'photos' => [],
                    'seller_asking_price' => '200.00', 'acquisition_price' => '150.00',
                    'public_price' => '210.00', 'markup' => '60.00',
                    'created_at' => '2026-09-11T10:00:00Z',
                    'sale' => null,
                ],
            ]]),
            'fati-api.alertaraqc.com/api/*' => Http::response(['data' => []]),
        ]);
    }

    private function page()
    {
        return $this->withSession($this->session)->get('/inventory/sold-items');
    }

    public function test_the_table_says_who_bought_it_and_how_they_paid(): void
    {
        $response = $this->page();

        $response->assertOk();
        $response->assertSee('Sheryl Cris Carigma');
        $response->assertSee('FM-000015');
        $response->assertSee('Gcash');
        $response->assertSee('₱225.00');
        $response->assertSee('5 point(s) used');
    }

    public function test_the_table_shows_the_points_the_buyer_earned(): void
    {
        $response = $this->page();

        $response->assertSee('Points earned');
        $response->assertSee('+2 point(s)');

        // And says so plainly when a sale earned none.
        $response->assertSee('None');
    }

    public function test_a_row_with_no_order_on_record_says_so(): void
    {
        $this->page()->assertSee('No order on record');
    }

    public function test_a_sold_item_cannot_be_edited(): void
    {
        $response = $this->page();

        $response->assertDontSee('edit-item-btn', false);
        $response->assertDontSee('openItemEdit', false);
        $response->assertDontSee('itemEditModal', false);
    }

    public function test_viewing_a_sold_item_opens_on_its_sale(): void
    {
        $response = $this->page();

        $response->assertSee('openItemView', false);
        $response->assertSee('The sale', false);
        $response->assertSee('Amount paid', false);
        $response->assertSee('Buyer earned', false);
        $response->assertSee('Payment method', false);
    }
}
