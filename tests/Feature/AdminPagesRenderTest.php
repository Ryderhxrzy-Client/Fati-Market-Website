<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every admin page renders with a signed-in session and a faked API.
 *
 * The console has no data of its own - every page is a view over the API -
 * so the cheapest regression check is to render each one against canned
 * responses and make sure nothing in the Blade or its includes blows up.
 */
class AdminPagesRenderTest extends TestCase
{
    private array $session = [
        'admin_token' => 'test-token',
        'admin_data' => [
            'user_id' => 1,
            'first_name' => 'Ofelia',
            'last_name' => 'Store',
            'email' => 'admin@fatimarket.com',
            'role' => 'admin',
        ],
        'admin_first_name' => 'Ofelia',
        'admin_last_name' => 'Store',
        'login_timestamp' => 0,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->session['login_timestamp'] = time();

        Http::fake([
            'fati-api.alertaraqc.com/api/admin/settings/gcash' => Http::response(['data' => [
                'account_name' => 'Ofelia', 'account_number' => '09171234567', 'qr_image_url' => null,
            ]]),
            'fati-api.alertaraqc.com/api/admin/dashboard' => Http::response(['data' => [
                'users' => ['total_students' => 3, 'active_students' => 2, 'pending_students' => 1, 'verified_students' => 2],
                'items' => ['total_items' => 4, 'private_items' => 1, 'public_items' => 1, 'acquired_items' => 1, 'reserved_items' => 0, 'sold_items' => 1],
                'recent_activities' => [
                    'recent_registrations' => [['name' => 'Juan', 'email' => 'juan@student.fatima.edu.ph']],
                    'recent_items' => [['title' => 'Calculator', 'seller' => 'juan@student.fatima.edu.ph', 'status' => 'pending']],
                    'pending_verifications' => [['student_name' => 'Juan', 'email' => 'juan@student.fatima.edu.ph']],
                ],
            ]]),
            'fati-api.alertaraqc.com/api/categories' => Http::response(['data' => [
                ['category_id' => 1, 'name' => 'Textbooks', 'description' => 'Books', 'item_count' => 2],
            ]]),
            'fati-api.alertaraqc.com/api/admin/items*' => Http::response(['data' => [[
                'item_id' => 7, 'seller_id' => 2, 'seller_email' => 'juan@student.fatima.edu.ph', 'title' => 'Calculator',
                'description' => 'Works', 'status' => 'acquired', 'photos' => [], 'created_at' => '2026-09-01T10:00:00Z',
                'seller_asking_price' => '150.00', 'acquisition_price' => '150.00', 'seller_payout_status' => 'unpaid',
            ]]]),
            'fati-api.alertaraqc.com/api/admin/transactions/profit-summary' => Http::response(['data' => [
                'total_profit_points' => 10, 'monthly_profit_points' => 5, 'completed_transactions' => 2, 'average_profit_per_transaction' => 5,
            ]]),
            'fati-api.alertaraqc.com/api/*' => Http::response(['data' => []]),
        ]);
    }

    public static function pages(): array
    {
        return [
            ['/dashboard'],
            ['/counter'],
            ['/inventory/private-offers'],
            ['/inventory/acquired-items'],
            ['/inventory/public-listings'],
            ['/inventory/reserved-items'],
            ['/inventory/sold-items'],
            ['/transactions/history'],
            ['/transactions/cash'],
            ['/transactions/trade'],
            ['/transactions/points-given'],
            ['/transactions/points-received'],
            ['/transactions/profit'],
            ['/reports/items-acquired'],
            ['/reports/items-sold'],
            ['/reports/profit'],
            ['/reports/categories'],
            ['/reports/users'],
            ['/categories'],
            ['/activity'],
            ['/conversations'],
            ['/students'],
            ['/profile'],
            ['/settings'],
        ];
    }

    #[DataProvider('pages')]
    public function test_admin_page_renders(string $path): void
    {
        $response = $this->withSession($this->session)->get($path);

        $response->assertOk();
        $response->assertSee('Fati Market', false);
    }

    public function test_settings_page_carries_store_hours_location_and_gcash(): void
    {
        $response = $this->withSession($this->session)->get('/settings');

        $response->assertOk();
        $response->assertSee('Store hours &amp; booking slots', false);
        $response->assertSee('Hollywood Terraces, Sumulong Hwy, Antipolo, 1870, Rizal', false);
        $response->assertSee('GCash payment settings', false);
        $response->assertDontSee('Delete Admin Account', false);
    }

    public function test_counter_page_includes_workflow_and_order_actions(): void
    {
        $response = $this->withSession($this->session)->get('/counter');

        $response->assertOk();
        $response->assertSee('openItemWorkflow', false);
        $response->assertSee('window.FMOrders', false);
        $response->assertSee('jsQR', false);
    }

    public function test_layout_carries_counter_link_and_notification_bell(): void
    {
        $response = $this->withSession($this->session)->get('/dashboard');

        $response->assertOk();
        $response->assertSee(route('admin.counter'), false);
        $response->assertSee('id="notifBadge"', false);
        $response->assertSee('notifications/chat', false);
    }

    public function test_guest_is_redirected_from_admin_pages(): void
    {
        $this->get('/counter')->assertRedirect('/');
    }
}
