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
                'description' => 'Works', 'status' => 'acquired', 'created_at' => '2026-09-01T10:00:00Z',
                'photos' => ['https://cdn.test/one.jpg', 'https://cdn.test/two.jpg', 'https://cdn.test/three.jpg'],
                'seller_asking_price' => '150.00', 'acquisition_price' => '150.00', 'seller_payout_status' => 'unpaid',
            ]]]),
            'fati-api.alertaraqc.com/api/admin/transactions/profit-summary' => Http::response(['data' => [
                'total_profit_points' => 10, 'monthly_profit_points' => 5, 'completed_transactions' => 2, 'average_profit_per_transaction' => 5,
            ]]),
            'fati-api.alertaraqc.com/api/admin/activity*' => Http::response(['data' => [[
                'action' => 'purchase',
                'user' => 'Ofelia Store',
                'user_id' => 1,
                'user_photo' => 'https://cdn.test/ofelia.jpg',
                'user_role' => 'admin',
                'user_email' => 'ofelia@fatima.edu.ph',
                'description' => 'Handed "Living in the IT Era" over to Sheryl Cris Carigma',
                'resource_type' => 'order',
                'resource_id' => 15,
                'timestamp' => '2026-09-16 18:11:00',
                'subject' => ['user_id' => 2, 'name' => 'Sheryl Cris Carigma', 'photo' => 'https://cdn.test/sheryl.jpg', 'role' => 'student', 'email' => 's@student.fatima.edu.ph'],
                'details' => [['label' => 'Buyer', 'value' => 'Sheryl Cris Carigma'], ['label' => 'Amount due', 'value' => '250.00']],
            ]]]),
            'fati-api.alertaraqc.com/api/*' => Http::response(['data' => []]),
        ]);
    }

    public static function pages(): array
    {
        return [
            ['/dashboard'],
            ['/inventory/private-offers'],
            ['/inventory/acquired-items'],
            ['/inventory/public-listings'],
            ['/inventory/reserved-items'],
            ['/inventory/sold-items'],
            ['/transactions/history'],
            ['/transactions/manage'],
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

    /** COUNTER SCAN DISABLED - the page is off, so the console must not link to it. */
    public function test_the_counter_scan_page_is_switched_off(): void
    {
        $this->withSession($this->session)->get('/counter')->assertNotFound();

        $dashboard = $this->withSession($this->session)->get('/dashboard');
        $dashboard->assertOk();
        $dashboard->assertDontSee('Scan at the counter');
        $dashboard->assertDontSee('Counter &middot; scan', false);
    }

    /**
     * The dashboard is a summary of the store, not a queue of approvals.
     *
     * Meet-ups went with the booking flow, and student verification is not
     * something the console decides any more, so neither is advertised here -
     * even when the API still sends the numbers behind them.
     */
    public function test_dashboard_has_no_meetups_or_approval_queues(): void
    {
        $response = $this->withSession($this->session)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('Meet-ups');
        $response->assertDontSee('Pending verifications');
        $response->assertDontSee('Pending approval');
        $response->assertDontSee('loadMeetups', false);

        // What it does still carry.
        $response->assertSee('Recent items');
        $response->assertSee('Store today');
    }

    public function test_chat_carries_the_turnover_panel(): void
    {
        $response = $this->withSession($this->session)->get('/conversations');

        $response->assertOk();
        $response->assertSee('openTurnover', false);
        $response->assertSee('Continue on phone');
        $response->assertSee('Do it here');
    }

    /**
     * The feed is about people, so it has to carry their faces and open on
     * the facts. It used to draw one gradient circle for every row.
     */
    public function test_activity_rows_carry_faces_and_their_details(): void
    {
        $response = $this->withSession($this->session)->get('/activity');

        $response->assertOk();
        $response->assertSee('https://cdn.test/ofelia.jpg', false);
        $response->assertSee('https://cdn.test/sheryl.jpg', false);
        $response->assertSee('Sheryl Cris Carigma');
        $response->assertSee('openActivity', false);
        $response->assertSee('Amount due');

        // The old anonymous circle is gone.
        $response->assertDontSee('from-green-400 to-blue-500', false);
    }

    /**
     * A picker with an upload button beside it leaves a photo chosen but not
     * added, and the list below goes on showing the old pictures.
     */
    public function test_the_workflow_panel_adds_photos_as_they_are_chosen(): void
    {
        $response = $this->withSession($this->session)->get('/inventory/acquired-items');

        $response->assertOk();
        $response->assertSee('onchange="wfUploadPhotos()"', false);
        $response->assertDontSee('>Upload photos<', false);
    }

    /**
     * Every inventory page used to carry its own copy of this window, and
     * every copy showed the first photo and stopped: a listing with five
     * pictures looked exactly like one with a single picture.
     *
     * @param  string  $path
     */
    #[DataProvider('inventoryPages')]
    public function test_viewing_an_item_shows_all_of_its_photos(string $path): void
    {
        $response = $this->withSession($this->session)->get($path);

        $response->assertOk();
        $response->assertSee('openItemView', false);
        $response->assertSee('itemViewStep', false);
        $response->assertSee('id="itemViewModal"', false);

        // The per-page copies are gone.
        $response->assertDontSee('function showViewModal', false);
    }

    public static function inventoryPages(): array
    {
        return [
            ['/inventory/private-offers'],
            ['/inventory/acquired-items'],
            ['/inventory/public-listings'],
            ['/inventory/reserved-items'],
            ['/inventory/sold-items'],
        ];
    }

    public function test_the_sidebar_has_a_profile_entry(): void
    {
        $response = $this->withSession($this->session)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('data-route="admin.profile"', false);
    }

    /**
     * The header blurs what is behind it, which makes it its own stacking
     * context: without a z-index of its own, the page painted over its menus
     * and the Google map on Settings swallowed the account menu whole.
     */
    public function test_the_header_is_lifted_above_the_page(): void
    {
        $response = $this->withSession($this->session)->get('/settings');

        $response->assertOk();
        $response->assertSee('z-index: 60;', false);
    }

    public function test_layout_carries_the_notification_bell(): void
    {
        $response = $this->withSession($this->session)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('id="notifBadge"', false);
        $response->assertSee('notifications/chat', false);
    }

    public function test_guest_is_redirected_from_admin_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/admin/login');
    }
}
