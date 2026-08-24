<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Reports;

use App\Models\Design;
use App\Models\DesignerProfile;
use App\Models\DesignProductMapping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PrinterProviderProfile;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_admin_can_view_overview(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/reports/admin/overview')
            ->assertOk();

        $json = $response->json();
        $this->assertIsArray($json);
        $this->assertNotEmpty($json);

        $metrics = array_column($json, 'metric');
        $this->assertContains('total_customers', $metrics);
        $this->assertContains('revenue_today', $metrics);
    }

    public function test_customer_cannot_view_admin_report(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/reports/admin/overview')
            ->assertForbidden();
    }

    public function test_unknown_report_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/reports/foo/bar')
            ->assertNotFound();
    }

    public function test_csv_format_streams_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Order::factory()->count(2)->create(['status' => 'pending']);

        $response = $this->actingAs($admin, 'sanctum')
            ->get('/api/reports/admin/order-status-distribution?format=csv');

        $response->assertOk();
        $this->assertSame('text/csv; charset=utf-8', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));

        $body = $response->streamedContent();
        $this->assertStringStartsWith('status,count', $body);
    }

    public function test_designer_dashboard_returns_totals(): void
    {
        $user = User::factory()->create(['role' => 'designer']);
        $profile = DesignerProfile::factory()->create(['user_id' => $user->id]);

        Design::factory()->published()->forDesigner($profile)->count(3)->create();
        Design::factory()->draft()->forDesigner($profile)->count(2)->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/designer/dashboard')
            ->assertOk();

        $json = $response->json();
        $metrics = array_column($json, 'metric');

        $this->assertContains('published_designs', $metrics);
        $this->assertContains('total_sales', $metrics);
        $this->assertContains('total_revenue', $metrics);

        $byMetric = collect($json)->keyBy('metric');
        $this->assertSame(3, $byMetric['published_designs']['value']);
    }

    public function test_designer_without_profile_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'designer']);
        // No designer profile attached.

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/reports/designer/dashboard')
            ->assertForbidden();
    }

    public function test_printer_work_queue_returns_only_assigned_items(): void
    {
        $printerUser = User::factory()->create(['role' => 'printer_provider']);
        $printer = PrinterProviderProfile::factory()->create(['user_id' => $printerUser->id]);

        $otherUser = User::factory()->create(['role' => 'printer_provider']);
        $otherPrinter = PrinterProviderProfile::factory()->create(['user_id' => $otherUser->id]);

        $customer = User::factory()->create(['role' => 'customer']);
        $template = ProductTemplate::factory()->create(['printer_provider_id' => $printer->id]);
        $mapping = DesignProductMapping::factory()->create([
            'preferred_printer_id' => $printer->id,
            'product_template_id' => $template->id,
        ]);

        $ownOrder = Order::factory()->forCustomer($customer)->create();
        OrderItem::factory()->create([
            'order_id' => $ownOrder->id,
            'design_product_mapping_id' => $mapping->id,
            'printer_provider_id' => $printer->id,
            'status' => 'pending',
        ]);

        $otherOrder = Order::factory()->forCustomer($customer)->create();
        OrderItem::factory()->create([
            'order_id' => $otherOrder->id,
            'design_product_mapping_id' => $mapping->id,
            'printer_provider_id' => $otherPrinter->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($printerUser, 'sanctum')
            ->getJson('/api/reports/printer/work-queue')
            ->assertOk();

        $rows = $response->json();
        $this->assertCount(1, $rows);
        $this->assertSame($ownOrder->id, $rows[0]['order_id']);
    }

    public function test_printer_work_queue_excludes_completed_items(): void
    {
        $printerUser = User::factory()->create(['role' => 'printer_provider']);
        $printer = PrinterProviderProfile::factory()->create(['user_id' => $printerUser->id]);

        $customer = User::factory()->create(['role' => 'customer']);
        $template = ProductTemplate::factory()->create(['printer_provider_id' => $printer->id]);
        $mapping = DesignProductMapping::factory()->create([
            'preferred_printer_id' => $printer->id,
            'product_template_id' => $template->id,
        ]);

        $order = Order::factory()->forCustomer($customer)->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'design_product_mapping_id' => $mapping->id,
            'printer_provider_id' => $printer->id,
            'status' => 'handed_off',
        ]);

        $this->actingAs($printerUser, 'sanctum')
            ->getJson('/api/reports/printer/work-queue')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_customer_order_history_returns_own_orders(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);

        Order::factory()->count(2)->forCustomer($customer)->create();
        Order::factory()->count(3)->forCustomer($otherCustomer)->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/reports/customer/order-history')
            ->assertOk();

        $payload = $response->json();
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertCount(2, $payload['data']);
        $this->assertSame(2, $payload['meta']['total']);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/reports/admin/overview')
            ->assertUnauthorized();
    }

    public function test_admin_revenue_by_designer_returns_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $designerUser = User::factory()->create(['role' => 'designer']);
        $designer = DesignerProfile::factory()->create(['user_id' => $designerUser->id]);

        $design = Design::factory()->published()->forDesigner($designer)->create();
        $printerUser = User::factory()->create(['role' => 'printer_provider']);
        $printer = PrinterProviderProfile::factory()->create(['user_id' => $printerUser->id]);
        $template = ProductTemplate::factory()->create(['printer_provider_id' => $printer->id]);
        $mapping = DesignProductMapping::factory()->create([
            'design_id' => $design->id,
            'product_template_id' => $template->id,
            'preferred_printer_id' => $printer->id,
        ]);

        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->forCustomer($customer)->create(['total_amount' => 250]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'design_product_mapping_id' => $mapping->id,
            'printer_provider_id' => $printer->id,
        ]);

        Payment::factory()->confirmed($admin)->create(['order_id' => $order->id]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/reports/admin/revenue-by-designer')
            ->assertOk();

        $rows = $response->json();
        $this->assertCount(1, $rows);
        $this->assertSame($designer->id, $rows[0]['designer_id']);
        $this->assertSame(250.0, (float) $rows[0]['revenue']);
    }

    public function test_admin_top_designs_limits_to_20(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $printerUser = User::factory()->create(['role' => 'printer_provider']);
        $printer = PrinterProviderProfile::factory()->create(['user_id' => $printerUser->id]);
        $template = ProductTemplate::factory()->create(['printer_provider_id' => $printer->id]);

        for ($i = 0; $i < 5; $i++) {
            $designerUser = User::factory()->create(['role' => 'designer']);
            $designer = DesignerProfile::factory()->create(['user_id' => $designerUser->id]);
            $design = Design::factory()->published()->forDesigner($designer)->create();
            $mapping = DesignProductMapping::factory()->create([
                'design_id' => $design->id,
                'product_template_id' => $template->id,
                'preferred_printer_id' => $printer->id,
            ]);

            $order = Order::factory()->forCustomer($customer)->create();
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'design_product_mapping_id' => $mapping->id,
                'printer_provider_id' => $printer->id,
            ]);
        }

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/reports/admin/top-designs')
            ->assertOk();

        $rows = $response->json();
        $this->assertLessThanOrEqual(20, count($rows));
        $this->assertGreaterThan(0, count($rows));
    }
}
