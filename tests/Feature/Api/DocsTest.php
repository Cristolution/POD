<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;

class DocsTest extends TestCase
{
    public function test_swagger_ui_renders_at_api_docs(): void
    {
        $response = $this->get('/api/docs');

        $response->assertOk();
        $response->assertSee('POD Platform API');
        $response->assertSee('SwaggerUIBundle');
        $response->assertSee('swagger-ui');
    }

    public function test_swagger_json_is_valid_and_lists_all_endpoints(): void
    {
        $response = $this->get('/api/docs.json');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');

        $spec = $response->json();

        $this->assertSame('POD Platform API', $spec['info']['title']);
        $this->assertSame('1.2.0', $spec['info']['version']);
        $this->assertSame('3.0.0', $spec['openapi']);

        // Every operationId declared in OpenApi.php must appear in the generated spec.
        // This is the full canonical list - extracted from storage/api-docs/api-docs.json.
        $expected = [
            'admin.audit.deleted',
            'admin.categories.destroy',
            'admin.categories.store',
            'admin.categories.update',
            'admin.dashboard',
            'admin.deliveryCompanies.destroy',
            'admin.deliveryCompanies.store',
            'admin.deliveryCompanies.update',
            'admin.designs.transfer',
            'admin.integrity.itemsWithoutShipment',
            'admin.integrity.orphanedMedia',
            'admin.integrity.profileMismatches',
            'admin.integrity.stuckCartItems',
            'admin.notifications.store',
            'admin.orders.destroy',
            'admin.orders.items.reassignPrinter',
            'admin.orders.restore',
            'admin.payments.destroy',
            'admin.settings.store',
            'admin.settings.update',
            'admin.tags.destroy',
            'admin.tags.store',
            'admin.tags.update',
            'admin.users.destroy',
            'admin.users.index',
            'admin.users.restore',
            'admin.users.update',
            'auth.forgotPassword',
            'auth.login',
            'auth.logout',
            'auth.register',
            'auth.resetPassword',
            'categories.index',
            'categories.show',
            'deliveryCompanies.index',
            'deliveryCompanies.show',
            'designers.index',
            'designers.show',
            'designs.destroy',
            'designs.index',
            'designs.media.store',
            'designs.restore',
            'designs.show',
            'designs.store',
            'designs.update',
            'health',
            'mappings.index',
            'mappings.show',
            'me.addresses.destroy',
            'me.addresses.index',
            'me.addresses.show',
            'me.addresses.store',
            'me.addresses.update',
            'me.cart.clear',
            'me.cart.items.destroy',
            'me.cart.items.store',
            'me.cart.items.update',
            'me.cart.show',
            'me.designerProfile.destroy',
            'me.designerProfile.store',
            'me.designerProfile.update',
            'me.destroy',
            'me.mappings.destroy',
            'me.mappings.store',
            'me.mappings.update',
            'me.notifications.destroy',
            'me.notifications.index',
            'me.notifications.markAllRead',
            'me.notifications.markRead',
            'me.notifications.unreadCount',
            'me.payments.index',
            'me.printerProfile.destroy',
            'me.printerProfile.store',
            'me.printerProfile.update',
            'me.show',
            'me.templates.destroy',
            'me.templates.store',
            'me.templates.update',
            'me.templates.variants.store',
            'me.update',
            'me.updatePassword',
            'me.variants.destroy',
            'me.variants.update',
            'media.destroy',
            'media.show',
            'orders.cancel',
            'orders.index',
            'orders.items.cancel',
            'orders.items.index',
            'orders.items.updateStatus',
            'orders.payments.store',
            'orders.shipments.store',
            'orders.show',
            'orders.store',
            'orders.updateStatus',
            'payments.confirm',
            'payments.media.store',
            'payments.reject',
            'payments.show',
            'printers.index',
            'printers.show',
            'reports.admin.customerLtv',
            'reports.admin.overview',
            'reports.admin.revenueByDay',
            'reports.admin.revenueByDesigner',
            'reports.admin.revenueByPrinter',
            'reports.admin.topDesigns',
            'reports.designer.dashboard',
            'reports.designer.payout',
            'reports.me.orders',
            'reports.me.shipmentsActive',
            'reports.ops.cartAbandonment',
            'reports.ops.stuckPayments',
            'reports.ops.stuckShipments',
            'reports.printer.payout',
            'reports.printer.workQueue',
            'settings.index',
            'settings.show',
            'shipments.destroy',
            'shipments.index',
            'shipments.show',
            'shipments.update',
            'tags.index',
            'templates.index',
            'templates.show',
            'users.show',
        ];

        $flat = [];
        foreach ($spec['paths'] as $methods) {
            foreach ($methods as $operation) {
                if (isset($operation['operationId'])) {
                    $flat[] = $operation['operationId'];
                }
            }
        }

        $this->assertCount(count($expected), $flat, 'Spec operationId count drift');

        foreach ($expected as $id) {
            $this->assertContains($id, $flat, "Missing operationId: {$id}");
        }
    }

    public function test_swagger_json_lists_every_documented_tag(): void
    {
        $response = $this->get('/api/docs.json');
        $spec = $response->json();

        $expectedTags = [
            'Admin',
            'Auth',
            'Cart',
            'Catalog',
            'DeliveryCompanies',
            'Designers',
            'Me',
            'Media',
            'Notifications',
            'OrderItems',
            'Orders',
            'Payments',
            'Printers',
            'Reports',
            'Settings',
            'Shipments',
            'Users',
        ];

        $actualTags = array_column($spec['tags'] ?? [], 'name');

        $this->assertCount(count($expectedTags), $actualTags, 'Spec tag count drift');

        foreach ($expectedTags as $tag) {
            $this->assertContains($tag, $actualTags, "Missing tag: {$tag}");
        }
    }
}
