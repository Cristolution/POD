<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

/**
 * OpenAPI annotation aggregator for the POD Platform API.
 *
 * This class is intentionally empty at the PHP level. It exists solely to host
 * OpenAPI annotation attributes that the swagger-php generator scans when
 * `php artisan l5-swagger:generate` runs.
 *
 * In Phase 2 these annotations will be migrated onto the actual controllers
 * (AuthController, CatalogController, OrderController, ...) and this file
 * will be deleted.
 *
 * @see https://zircote.github.io/swagger-php/
 */
#[OA\Info(
    version: '1.2.0',
    title: 'POD Platform API',
    description: 'REST API for the POD (Print-on-Demand) platform. All endpoints are mounted under /api/*. Authentication uses Sanctum bearer tokens for protected routes.',
    contact: new OA\Contact(name: 'POD Platform', email: 'api@pod.example.com'),
    license: new OA\License(name: 'Proprietary'),
)]
#[OA\OpenApi(
    servers: [
        new OA\Server(
            url: 'http://localhost:8000',
            description: 'Local development',
        ),
        new OA\Server(
            url: 'https://staging.pod.example.com',
            description: 'Staging',
        ),
        new OA\Server(
            url: 'https://pod.example.com',
            description: 'Production',
        ),
    ],
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum token',
    description: 'Sanctum personal access token. Obtain via POST /api/auth/login or POST /api/auth/register.',
)]
#[OA\PathItem(
    path: '/api/health',
    description: 'Liveness probe. Placeholder PathItem to satisfy swagger-php v3.0 generation requirement. Phase 2 replaces this with a real HealthController.',
)]
#[OA\Get(
    path: '/api/health',
    operationId: 'health',
    tags: ['Admin'],
    summary: 'Liveness probe',
    description: 'Returns 200 OK if the API is reachable. No authentication required.',
    responses: [
        new OA\Response(response: 200, description: 'API is reachable'),
    ],
)]
#[OA\Tag(name: 'Auth', description: 'Registration, login, logout, password reset')]
#[OA\Tag(name: 'Me', description: 'Current user self-management (profile, addresses, cart, orders, payments, notifications)')]
#[OA\Tag(name: 'Users', description: 'Public user reads + admin user CRUD')]
#[OA\Tag(name: 'Designers', description: 'Designer directory + per-designer profiles')]
#[OA\Tag(name: 'Printers', description: 'Printer directory + per-printer profiles')]
#[OA\Tag(name: 'Catalog', description: 'Categories, tags, designs, templates, variants, mappings')]
#[OA\Tag(name: 'Cart', description: 'Authenticated user cart')]
#[OA\Tag(name: 'Orders', description: 'Order placement, reads, status, cancellation')]
#[OA\Tag(name: 'OrderItems', description: 'Per-item status updates and printer reassignment')]
#[OA\Tag(name: 'Shipments', description: 'Shipment creation and tracking')]
#[OA\Tag(name: 'DeliveryCompanies', description: 'Public reads + admin CRUD')]
#[OA\Tag(name: 'Payments', description: 'Payment creation, confirmation, rejection')]
#[OA\Tag(name: 'Media', description: 'Polymorphic uploads (design files, payment proofs)')]
#[OA\Tag(name: 'Notifications', description: 'Authenticated user notifications + admin broadcast')]
#[OA\Tag(name: 'Settings', description: 'Key/value settings (public reads gated by is_public)')]
#[OA\Tag(name: 'Admin', description: 'Admin dashboard, audit, integrity checks')]
#[OA\Tag(name: 'Reports', description: '15 reports — admin, designer, printer scoped')]
// ─── Schemas ─────────────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'User',
    description: 'Public user. Email and phone are redacted unless the requester is the user themselves or an admin.',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'designer', 'printer_provider', 'customer']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
    required: ['id', 'name', 'role'],
)]
#[OA\Schema(
    schema: 'UserWithToken',
    description: 'User shape returned by /api/auth/login and /api/auth/register. Includes the Sanctum bearer token.',
    allOf: [new OA\Schema(ref: '#/components/schemas/User')],
    properties: [
        new OA\Property(property: 'token', type: 'string'),
    ],
    required: ['token'],
)]
#[OA\Schema(
    schema: 'UserCollection',
    type: 'array',
    items: new OA\Items(ref: '#/components/schemas/User'),
)]
#[OA\Schema(
    schema: 'Address',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'label', type: 'string', nullable: true),
        new OA\Property(property: 'line1', type: 'string'),
        new OA\Property(property: 'line2', type: 'string', nullable: true),
        new OA\Property(property: 'city', type: 'string'),
        new OA\Property(property: 'postal_code', type: 'string'),
        new OA\Property(property: 'country', type: 'string', minLength: 2, maxLength: 2),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    required: ['id', 'user_id', 'line1', 'city', 'postal_code', 'country'],
)]
#[OA\Schema(
    schema: 'DesignerProfile',
    properties: [
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'bio', type: 'string', nullable: true),
        new OA\Property(property: 'links', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'), nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    required: ['user_id'],
)]
#[OA\Schema(
    schema: 'PrinterProviderProfile',
    properties: [
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'company_name', type: 'string'),
        new OA\Property(property: 'capabilities', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'), nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    required: ['user_id', 'company_name'],
)]
#[OA\Schema(
    schema: 'Category',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'children', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category'), nullable: true),
        new OA\Property(property: 'breadcrumb', type: 'array', items: new OA\Items(ref: '#/components/schemas/Category'), nullable: true),
    ],
    required: ['id', 'name', 'slug'],
)]
#[OA\Schema(
    schema: 'Tag',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
    ],
    required: ['id', 'name', 'slug'],
)]
#[OA\Schema(
    schema: 'Design',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'designer_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'category_id', type: 'integer', nullable: true),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'archived']),
        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(ref: '#/components/schemas/Tag'), nullable: true),
        new OA\Property(property: 'media', type: 'array', items: new OA\Items(ref: '#/components/schemas/Media'), nullable: true),
        new OA\Property(property: 'mappings', type: 'array', items: new OA\Items(ref: '#/components/schemas/DesignProductMapping'), nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    required: ['id', 'designer_id', 'title', 'status'],
)]
#[OA\Schema(
    schema: 'ProductTemplate',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'printer_provider_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'base_cost', type: 'number', format: 'float'),
        new OA\Property(property: 'specs', type: 'object', additionalProperties: new OA\AdditionalProperties, nullable: true),
        new OA\Property(property: 'variants', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductVariant'), nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    required: ['id', 'printer_provider_id', 'name', 'type', 'base_cost'],
)]
#[OA\Schema(
    schema: 'ProductVariant',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'product_template_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'attributes', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string')),
        new OA\Property(property: 'price_delta', type: 'number', format: 'float'),
        new OA\Property(property: 'sku', type: 'string'),
        new OA\Property(property: 'is_active', type: 'boolean'),
    ],
    required: ['id', 'product_template_id', 'attributes', 'price_delta', 'sku', 'is_active'],
)]
#[OA\Schema(
    schema: 'DesignProductMapping',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'design_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'product_template_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'preferred_printer_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'final_price', type: 'number', format: 'float'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    required: ['id', 'design_id', 'product_template_id', 'final_price'],
)]
#[OA\Schema(
    schema: 'CartItem',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'design_product_mapping_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'product_variant_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'quantity', type: 'integer', minimum: 1, maximum: 100),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float'),
        new OA\Property(property: 'line_total', type: 'number', format: 'float'),
        new OA\Property(property: 'design_product_mapping', ref: '#/components/schemas/DesignProductMapping'),
        new OA\Property(property: 'product_variant', ref: '#/components/schemas/ProductVariant', nullable: true),
    ],
    required: ['id', 'user_id', 'design_product_mapping_id', 'quantity', 'unit_price', 'line_total'],
)]
#[OA\Schema(
    schema: 'CartResponse',
    description: 'Cart with line totals and grand total envelope.',
    properties: [
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/CartItem')),
        new OA\Property(property: 'grand_total', type: 'number', format: 'float'),
    ],
    required: ['items', 'grand_total'],
)]
#[OA\Schema(
    schema: 'Order',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'customer_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
        new OA\Property(property: 'total', type: 'number', format: 'float'),
        new OA\Property(property: 'shipping_address', type: 'object', additionalProperties: new OA\AdditionalProperties),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderItem'), nullable: true),
        new OA\Property(property: 'payments', type: 'array', items: new OA\Items(ref: '#/components/schemas/Payment'), nullable: true),
        new OA\Property(property: 'shipments', type: 'array', items: new OA\Items(ref: '#/components/schemas/Shipment'), nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    required: ['id', 'customer_id', 'status', 'total'],
)]
#[OA\Schema(
    schema: 'OrderItem',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'order_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'design_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'product_variant_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'printer_provider_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'quantity', type: 'integer'),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float'),
        new OA\Property(property: 'line_total', type: 'number', format: 'float'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'received', 'printing', 'printed', 'handed_off', 'cancelled']),
    ],
    required: ['id', 'order_id', 'design_id', 'quantity', 'unit_price', 'line_total', 'status'],
)]
#[OA\Schema(
    schema: 'DeliveryCompany',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'coverage_zones', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'tracking_url_pattern', type: 'string', format: 'uri', nullable: true),
    ],
    required: ['id', 'name'],
)]
#[OA\Schema(
    schema: 'Shipment',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'order_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'printer_provider_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'delivery_company_id', type: 'integer', nullable: true),
        new OA\Property(property: 'tracking_number', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'shipped', 'delivered']),
        new OA\Property(property: 'tracking_url', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'shipped_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'delivered_at', type: 'string', format: 'date-time', nullable: true),
    ],
    required: ['id', 'order_id', 'printer_provider_id', 'status'],
)]
#[OA\Schema(
    schema: 'Payment',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'order_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'customer_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'method', type: 'string', enum: ['cash_on_delivery', 'bank_transfer', 'card']),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'confirmed', 'rejected']),
        new OA\Property(property: 'amount', type: 'number', format: 'float'),
        new OA\Property(property: 'confirmed_by_admin_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'confirmed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    required: ['id', 'order_id', 'customer_id', 'method', 'status', 'amount'],
)]
#[OA\Schema(
    schema: 'Media',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'model_type', type: 'string'),
        new OA\Property(property: 'model_id', type: 'string'),
        new OA\Property(property: 'collection_name', type: 'string', enum: ['mockup', 'print_file', 'attachment', 'payment_proof']),
        new OA\Property(property: 'file_name', type: 'string'),
        new OA\Property(property: 'mime_type', type: 'string'),
        new OA\Property(property: 'size', type: 'integer'),
        new OA\Property(property: 'url', type: 'string', format: 'uri'),
    ],
    required: ['id', 'model_type', 'model_id', 'collection_name', 'file_name', 'mime_type', 'size', 'url'],
)]
#[OA\Schema(
    schema: 'Notification',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'data', type: 'object', additionalProperties: new OA\AdditionalProperties),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
    required: ['id', 'type', 'data'],
)]
#[OA\Schema(
    schema: 'UnreadCount',
    properties: [new OA\Property(property: 'unread_count', type: 'integer')],
    required: ['unread_count'],
)]
#[OA\Schema(
    schema: 'MarkedCount',
    properties: [new OA\Property(property: 'marked', type: 'integer')],
    required: ['marked'],
)]
#[OA\Schema(
    schema: 'Setting',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'key', type: 'string'),
        new OA\Property(property: 'value', type: 'string', nullable: true),
        new OA\Property(property: 'is_public', type: 'boolean'),
    ],
    required: ['id', 'key', 'is_public'],
)]
final class OpenApi {}
