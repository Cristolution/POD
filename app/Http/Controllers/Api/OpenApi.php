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
// ─── Auth ────────────────────────────────────────────────────────────────────
#[OA\Post(
    path: '/api/auth/register',
    operationId: 'auth.register',
    tags: ['Auth'],
    summary: 'Register a new user.',
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreUserRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/UserWithToken')),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Post(
    path: '/api/auth/login',
    operationId: 'auth.login',
    tags: ['Auth'],
    summary: 'Authenticate and receive a Sanctum bearer token.',
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['email', 'password'],
        properties: [
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'password', type: 'string', format: 'password'),
        ]
    )),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/UserWithToken')),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Post(
    path: '/api/auth/logout',
    operationId: 'auth.logout',
    tags: ['Auth'],
    summary: 'Revoke the current Sanctum token.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Post(
    path: '/api/auth/forgot-password',
    operationId: 'auth.forgotPassword',
    tags: ['Auth'],
    summary: 'Send a password reset link.',
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['email'],
        properties: [new OA\Property(property: 'email', type: 'string', format: 'email')]
    )),
    responses: [
        new OA\Response(response: 202, description: 'Reset link dispatched (always returned)'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Post(
    path: '/api/auth/reset-password',
    operationId: 'auth.resetPassword',
    tags: ['Auth'],
    summary: 'Reset the password using the emailed token.',
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['token', 'email', 'password'],
        properties: [
            new OA\Property(property: 'token', type: 'string'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'password', type: 'string', format: 'password'),
        ]
    )),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/UserWithToken')),
        new OA\Response(response: 422, description: 'Token invalid/expired'),
    ]
)]
#[OA\Schema(
    schema: 'StoreUserRequest',
    required: ['name', 'email', 'password'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 120),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 180),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
        new OA\Property(property: 'phone', type: 'string', maxLength: 32, nullable: true),
        new OA\Property(property: 'role', type: 'string', enum: ['customer', 'designer', 'printer_provider'], default: 'customer'),
    ]
)]
// ─── Me ───────────────────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/me',
    operationId: 'me.show',
    tags: ['Me'],
    summary: 'Return the authenticated user.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Patch(
    path: '/api/me',
    operationId: 'me.update',
    tags: ['Me'],
    summary: 'Update profile. current_password required when password is set.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateUserRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/me/password',
    operationId: 'me.updatePassword',
    tags: ['Me'],
    summary: 'Change password.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['current_password', 'password'],
        properties: [
            new OA\Property(property: 'current_password', type: 'string', format: 'password'),
            new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
            new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
        ]
    )),
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated or wrong current_password'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/me',
    operationId: 'me.destroy',
    tags: ['Me'],
    summary: 'Soft-delete the authenticated user. 409 if designer/printer with active items.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 409, description: 'Active references block deletion'),
    ]
)]
#[OA\Schema(
    schema: 'UpdateUserRequest',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 120),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 180),
        new OA\Property(property: 'phone', type: 'string', maxLength: 32, nullable: true),
        new OA\Property(property: 'current_password', type: 'string', format: 'password'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
    ]
)]
// ─── Users ────────────────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/users/{uuid}',
    operationId: 'users.show',
    tags: ['Users'],
    summary: 'Public user read. Email/phone redacted unless requester is self or admin.',
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/admin/users',
    operationId: 'admin.users.index',
    tags: ['Users'],
    summary: 'Admin: paginated user list.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'role', in: 'query', schema: new OA\Schema(type: 'string', enum: ['admin', 'designer', 'printer_provider', 'customer'])),
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/UserCollection')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
    ]
)]
#[OA\Patch(
    path: '/api/admin/users/{uuid}',
    operationId: 'admin.users.update',
    tags: ['Users'],
    summary: 'Admin: update user (incl. role).',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateUserRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/admin/users/{uuid}',
    operationId: 'admin.users.destroy',
    tags: ['Users'],
    summary: 'Admin: soft-delete user.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/admin/users/{uuid}/restore',
    operationId: 'admin.users.restore',
    tags: ['Users'],
    summary: 'Admin: restore a soft-deleted user.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/User')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
// ─── Designers ────────────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/designers',
    operationId: 'designers.index',
    tags: ['Designers'],
    summary: 'Public: paginated designer directory.',
    parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/DesignerProfile')))]
)]
#[OA\Get(
    path: '/api/designers/{uuid}',
    operationId: 'designers.show',
    tags: ['Designers'],
    summary: 'Public: single designer (with designs whenLoaded).',
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DesignerProfile')),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/me/designer-profile',
    operationId: 'me.designerProfile.store',
    tags: ['Designers'],
    summary: 'Designer: create own profile.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreDesignerProfileRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/DesignerProfile')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not a designer'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/me/designer-profile',
    operationId: 'me.designerProfile.update',
    tags: ['Designers'],
    summary: 'Designer: update own profile.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateDesignerProfileRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DesignerProfile')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/me/designer-profile',
    operationId: 'me.designerProfile.destroy',
    tags: ['Designers'],
    summary: 'Designer: delete own profile. 409 if active designs.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 409, description: 'Active designs reference this profile'),
    ]
)]
#[OA\Schema(
    schema: 'StoreDesignerProfileRequest',
    properties: [
        new OA\Property(property: 'bio', type: 'string', nullable: true),
        new OA\Property(property: 'links', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'), nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'UpdateDesignerProfileRequest',
    properties: [
        new OA\Property(property: 'bio', type: 'string', nullable: true),
        new OA\Property(property: 'links', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'), nullable: true),
    ]
)]
// ─── Printers ──────────────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/printers',
    operationId: 'printers.index',
    tags: ['Printers'],
    summary: 'Public: paginated printer directory.',
    parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/PrinterProviderProfile')))]
)]
#[OA\Get(
    path: '/api/printers/{uuid}',
    operationId: 'printers.show',
    tags: ['Printers'],
    summary: 'Public: single printer (with productTemplates whenLoaded).',
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PrinterProviderProfile')),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/me/printer-profile',
    operationId: 'me.printerProfile.store',
    tags: ['Printers'],
    summary: 'Printer: create own profile.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StorePrinterProviderProfileRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/PrinterProviderProfile')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not a printer'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/me/printer-profile',
    operationId: 'me.printerProfile.update',
    tags: ['Printers'],
    summary: 'Printer: update own profile.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdatePrinterProviderProfileRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PrinterProviderProfile')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/me/printer-profile',
    operationId: 'me.printerProfile.destroy',
    tags: ['Printers'],
    summary: 'Printer: delete own profile. 409 if active templates.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 409, description: 'Active templates reference this profile'),
    ]
)]
#[OA\Schema(
    schema: 'StorePrinterProviderProfileRequest',
    required: ['company_name'],
    properties: [
        new OA\Property(property: 'company_name', type: 'string', maxLength: 180),
        new OA\Property(property: 'capabilities', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'), nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'UpdatePrinterProviderProfileRequest',
    properties: [
        new OA\Property(property: 'company_name', type: 'string', maxLength: 180),
        new OA\Property(property: 'capabilities', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'), nullable: true),
    ]
)]
// ─── Me / Addresses ──────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/me/addresses',
    operationId: 'me.addresses.index',
    tags: ['Me'],
    summary: 'List own addresses.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Address'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Post(
    path: '/api/me/addresses',
    operationId: 'me.addresses.store',
    tags: ['Me'],
    summary: 'Create address.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreAddressRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Address')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Get(
    path: '/api/me/addresses/{id}',
    operationId: 'me.addresses.show',
    tags: ['Me'],
    summary: 'Show own address.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Address')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Patch(
    path: '/api/me/addresses/{id}',
    operationId: 'me.addresses.update',
    tags: ['Me'],
    summary: 'Update own address.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateAddressRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Address')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/me/addresses/{id}',
    operationId: 'me.addresses.destroy',
    tags: ['Me'],
    summary: 'Delete own address.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Schema(
    schema: 'StoreAddressRequest',
    required: ['line1', 'city', 'postal_code', 'country'],
    properties: [
        new OA\Property(property: 'label', type: 'string', maxLength: 80, nullable: true),
        new OA\Property(property: 'line1', type: 'string', maxLength: 200),
        new OA\Property(property: 'line2', type: 'string', maxLength: 200, nullable: true),
        new OA\Property(property: 'city', type: 'string', maxLength: 120),
        new OA\Property(property: 'postal_code', type: 'string', maxLength: 32),
        new OA\Property(property: 'country', type: 'string', minLength: 2, maxLength: 2, description: 'ISO 3166-1 alpha-2'),
        new OA\Property(property: 'phone', type: 'string', maxLength: 32, nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'UpdateAddressRequest',
    properties: [
        new OA\Property(property: 'label', type: 'string', maxLength: 80, nullable: true),
        new OA\Property(property: 'line1', type: 'string', maxLength: 200),
        new OA\Property(property: 'line2', type: 'string', maxLength: 200, nullable: true),
        new OA\Property(property: 'city', type: 'string', maxLength: 120),
        new OA\Property(property: 'postal_code', type: 'string', maxLength: 32),
        new OA\Property(property: 'country', type: 'string', minLength: 2, maxLength: 2),
        new OA\Property(property: 'phone', type: 'string', maxLength: 32, nullable: true),
    ]
)]
// ─── Catalog / Categories ─────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/categories',
    operationId: 'categories.index',
    tags: ['Catalog'],
    summary: 'Public: list categories (tree optional).',
    parameters: [
        new OA\Parameter(name: 'parent_id', in: 'query', schema: new OA\Schema(type: 'integer', nullable: true)),
        new OA\Parameter(name: 'tree', in: 'query', schema: new OA\Schema(type: 'boolean', default: false)),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Category')))]
)]
#[OA\Get(
    path: '/api/categories/{id}',
    operationId: 'categories.show',
    tags: ['Catalog'],
    summary: 'Public: single category with breadcrumb.',
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/admin/categories',
    operationId: 'admin.categories.store',
    tags: ['Catalog'],
    summary: 'Admin: create category.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreCategoryRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/admin/categories/{id}',
    operationId: 'admin.categories.update',
    tags: ['Catalog'],
    summary: 'Admin: update category.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateCategoryRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Category')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/admin/categories/{id}',
    operationId: 'admin.categories.destroy',
    tags: ['Catalog'],
    summary: 'Admin: delete category. 409 if designs attached.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 409, description: 'Designs attached'),
    ]
)]
#[OA\Schema(
    schema: 'StoreCategoryRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 120),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'UpdateCategoryRequest',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 120),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
    ]
)]
// ─── Catalog / Tags ───────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/tags',
    operationId: 'tags.index',
    tags: ['Catalog'],
    summary: 'Public: list tags (autocomplete by prefix).',
    parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Tag')))]
)]
#[OA\Post(
    path: '/api/admin/tags',
    operationId: 'admin.tags.store',
    tags: ['Catalog'],
    summary: 'Admin: create tag.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreTagRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Tag')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/admin/tags/{id}',
    operationId: 'admin.tags.update',
    tags: ['Catalog'],
    summary: 'Admin: update tag.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateTagRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Tag')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/admin/tags/{id}',
    operationId: 'admin.tags.destroy',
    tags: ['Catalog'],
    summary: 'Admin: delete tag. 409 if designs attached.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 409, description: 'Designs attached'),
    ]
)]
#[OA\Schema(
    schema: 'StoreTagRequest',
    required: ['name'],
    properties: [new OA\Property(property: 'name', type: 'string', maxLength: 80)]
)]
#[OA\Schema(
    schema: 'UpdateTagRequest',
    properties: [new OA\Property(property: 'name', type: 'string', maxLength: 80)]
)]
// ─── Catalog / Designs ────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/designs',
    operationId: 'designs.index',
    tags: ['Catalog'],
    summary: 'Public: list published designs.',
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['published'], default: 'published')),
        new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'tag_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'designer_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: ['newest', 'oldest', 'popular'])),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Design')))]
)]
#[OA\Get(
    path: '/api/designs/{uuid}',
    operationId: 'designs.show',
    tags: ['Catalog'],
    summary: 'Public for published; owner/admin for draft/archived.',
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Design')),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/designs',
    operationId: 'designs.store',
    tags: ['Catalog'],
    summary: 'Designer: create design (status=draft).',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreDesignRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Design')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not a designer'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/designs/{uuid}',
    operationId: 'designs.update',
    tags: ['Catalog'],
    summary: 'Designer: update own design.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateDesignRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Design')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/designs/{uuid}',
    operationId: 'designs.destroy',
    tags: ['Catalog'],
    summary: 'Designer: soft-delete own design. 409 if order_items reference it.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 409, description: 'Order items reference this design'),
    ]
)]
#[OA\Post(
    path: '/api/designs/{uuid}/restore',
    operationId: 'designs.restore',
    tags: ['Catalog'],
    summary: 'Designer: restore own soft-deleted design.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Design')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/admin/designs/{uuid}/transfer',
    operationId: 'admin.designs.transfer',
    tags: ['Catalog'],
    summary: 'Admin: transfer design ownership (audit-logged).',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['new_designer_id'],
        properties: [new OA\Property(property: 'new_designer_id', type: 'string', format: 'uuid')]
    )),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Design')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Schema(
    schema: 'StoreDesignRequest',
    required: ['title'],
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 180),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'category_id', type: 'integer', nullable: true),
        new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer')),
    ]
)]
#[OA\Schema(
    schema: 'UpdateDesignRequest',
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 180),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'category_id', type: 'integer', nullable: true),
        new OA\Property(property: 'tag_ids', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'archived']),
    ]
)]
// ─── Catalog / Templates + Variants ───────────────────────────────────────────
#[OA\Get(
    path: '/api/templates',
    operationId: 'templates.index',
    tags: ['Catalog'],
    summary: 'Public: list product templates.',
    parameters: [
        new OA\Parameter(name: 'printer_provider_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductTemplate')))]
)]
#[OA\Get(
    path: '/api/templates/{uuid}',
    operationId: 'templates.show',
    tags: ['Catalog'],
    summary: 'Public: single template with variants.',
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/ProductTemplate')),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/me/templates',
    operationId: 'me.templates.store',
    tags: ['Catalog'],
    summary: 'Printer: create template.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreProductTemplateRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/ProductTemplate')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not a printer'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/me/templates/{uuid}',
    operationId: 'me.templates.update',
    tags: ['Catalog'],
    summary: 'Printer: update own template.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateProductTemplateRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/ProductTemplate')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/me/templates/{uuid}',
    operationId: 'me.templates.destroy',
    tags: ['Catalog'],
    summary: 'Printer: delete own template. 409 if order_items reference it.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 409, description: 'Order items reference this template'),
    ]
)]
#[OA\Post(
    path: '/api/me/templates/{uuid}/variants',
    operationId: 'me.templates.variants.store',
    tags: ['Catalog'],
    summary: 'Printer: add variant to own template.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreProductVariantRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/ProductVariant')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Template not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/me/variants/{uuid}',
    operationId: 'me.variants.update',
    tags: ['Catalog'],
    summary: 'Printer: update own variant.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateProductVariantRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/ProductVariant')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/me/variants/{uuid}',
    operationId: 'me.variants.destroy',
    tags: ['Catalog'],
    summary: 'Printer: delete own variant. 409 if order_items reference it.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 409, description: 'Order items reference this variant'),
    ]
)]
#[OA\Schema(
    schema: 'StoreProductTemplateRequest',
    required: ['name', 'type', 'base_cost'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 180),
        new OA\Property(property: 'type', type: 'string', maxLength: 80),
        new OA\Property(property: 'base_cost', type: 'number', format: 'float', minimum: 0),
        new OA\Property(property: 'specs', type: 'object', additionalProperties: new OA\AdditionalProperties, nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'UpdateProductTemplateRequest',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 180),
        new OA\Property(property: 'type', type: 'string', maxLength: 80),
        new OA\Property(property: 'base_cost', type: 'number', format: 'float', minimum: 0),
        new OA\Property(property: 'specs', type: 'object', additionalProperties: new OA\AdditionalProperties, nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'StoreProductVariantRequest',
    required: ['attributes', 'price_delta', 'sku'],
    properties: [
        new OA\Property(property: 'attributes', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string')),
        new OA\Property(property: 'price_delta', type: 'number', format: 'float'),
        new OA\Property(property: 'sku', type: 'string', maxLength: 80),
        new OA\Property(property: 'is_active', type: 'boolean', default: true),
    ]
)]
#[OA\Schema(
    schema: 'UpdateProductVariantRequest',
    properties: [
        new OA\Property(property: 'attributes', type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string')),
        new OA\Property(property: 'price_delta', type: 'number', format: 'float'),
        new OA\Property(property: 'sku', type: 'string', maxLength: 80),
        new OA\Property(property: 'is_active', type: 'boolean'),
    ]
)]
// ─── Catalog / Mappings ────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/mappings',
    operationId: 'mappings.index',
    tags: ['Catalog'],
    summary: 'Public: list design-to-template mappings.',
    parameters: [
        new OA\Parameter(name: 'design_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'product_template_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/DesignProductMapping')))]
)]
#[OA\Get(
    path: '/api/mappings/{uuid}',
    operationId: 'mappings.show',
    tags: ['Catalog'],
    summary: 'Public: single mapping.',
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DesignProductMapping')),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/me/mappings',
    operationId: 'me.mappings.store',
    tags: ['Catalog'],
    summary: 'Designer: create mapping. 409 on duplicate (design, template).',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreDesignProductMappingRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/DesignProductMapping')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not a designer'),
        new OA\Response(response: 409, description: 'Duplicate mapping'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/me/mappings/{uuid}',
    operationId: 'me.mappings.update',
    tags: ['Catalog'],
    summary: 'Designer: update own mapping.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateDesignProductMappingRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DesignProductMapping')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/me/mappings/{uuid}',
    operationId: 'me.mappings.destroy',
    tags: ['Catalog'],
    summary: 'Designer: delete own mapping.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Schema(
    schema: 'StoreDesignProductMappingRequest',
    required: ['design_id', 'product_template_id', 'final_price'],
    properties: [
        new OA\Property(property: 'design_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'product_template_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'final_price', type: 'number', format: 'float', minimum: 0),
        new OA\Property(property: 'preferred_printer_id', type: 'string', format: 'uuid', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'UpdateDesignProductMappingRequest',
    properties: [
        new OA\Property(property: 'final_price', type: 'number', format: 'float', minimum: 0),
        new OA\Property(property: 'preferred_printer_id', type: 'string', format: 'uuid', nullable: true),
    ]
)]
// ─── Cart ──────────────────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/me/cart',
    operationId: 'me.cart.show',
    tags: ['Cart'],
    summary: 'Get own cart (items + grand total envelope).',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/CartResponse')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Post(
    path: '/api/me/cart/items',
    operationId: 'me.cart.items.store',
    tags: ['Cart'],
    summary: 'Add item (upserts on duplicate line per FR-6.6).',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreCartItemRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/CartItem')),
        new OA\Response(response: 200, description: 'Updated (existing line quantity increased)'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/me/cart/items/{id}',
    operationId: 'me.cart.items.update',
    tags: ['Cart'],
    summary: 'Update own cart item quantity.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateCartItemRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/CartItem')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Item not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/me/cart/items/{id}',
    operationId: 'me.cart.items.destroy',
    tags: ['Cart'],
    summary: 'Remove a single cart item.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Item not found'),
    ]
)]
#[OA\Delete(
    path: '/api/me/cart',
    operationId: 'me.cart.clear',
    tags: ['Cart'],
    summary: 'Clear entire cart.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Schema(
    schema: 'StoreCartItemRequest',
    required: ['design_product_mapping_id', 'quantity'],
    properties: [
        new OA\Property(property: 'design_product_mapping_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'product_variant_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'quantity', type: 'integer', minimum: 1, maximum: 100),
    ]
)]
#[OA\Schema(
    schema: 'UpdateCartItemRequest',
    required: ['quantity'],
    properties: [
        new OA\Property(property: 'quantity', type: 'integer', minimum: 1, maximum: 100),
    ]
)]
// ─── Orders ────────────────────────────────────────────────────────────────────
#[OA\Post(
    path: '/api/orders',
    operationId: 'orders.store',
    tags: ['Orders'],
    summary: 'Place an order from the current cart. Hard-deletes cart on success (DB transaction).',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreOrderRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created (with items + initial payment)', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Cart empty or validation failed'),
    ]
)]
#[OA\Get(
    path: '/api/orders',
    operationId: 'orders.index',
    tags: ['Orders'],
    summary: 'List orders: customer sees own; admin sees all.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])),
        new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Order'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not customer/admin'),
    ]
)]
#[OA\Get(
    path: '/api/orders/{uuid}',
    operationId: 'orders.show',
    tags: ['Orders'],
    summary: 'Single order (customer own / designer own designs / printer own items / admin).',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not allowed'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Patch(
    path: '/api/orders/{uuid}/cancel',
    operationId: 'orders.cancel',
    tags: ['Orders'],
    summary: 'Customer (own, before processing) / admin. Fires OrderCancelled.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(content: new OA\JsonContent(
        required: ['reason'],
        properties: [new OA\Property(property: 'reason', type: 'string', maxLength: 500)]
    )),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not allowed / order already processing'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/orders/{uuid}/status',
    operationId: 'orders.updateStatus',
    tags: ['Orders'],
    summary: 'Admin: force-set status (audit-logged).',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['status'],
        properties: [new OA\Property(property: 'status', type: 'string', enum: ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])]
    )),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Delete(
    path: '/api/admin/orders/{uuid}',
    operationId: 'admin.orders.destroy',
    tags: ['Orders'],
    summary: 'Admin: soft-delete order.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/admin/orders/{uuid}/restore',
    operationId: 'admin.orders.restore',
    tags: ['Orders'],
    summary: 'Admin: restore soft-deleted order.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Schema(
    schema: 'StoreOrderRequest',
    required: ['payment_method'],
    properties: [
        new OA\Property(property: 'shipping_address_id', type: 'integer', nullable: true, description: 'If omitted, snapshot fields below are required'),
        new OA\Property(property: 'shipping_address', ref: '#/components/schemas/StoreAddressRequest', nullable: true),
        new OA\Property(property: 'payment_method', type: 'string', enum: ['cash_on_delivery', 'bank_transfer', 'card']),
    ]
)]
// ─── Order Items ───────────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/orders/{uuid}/items',
    operationId: 'orders.items.index',
    tags: ['OrderItems'],
    summary: 'List items for an order.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderItem'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not allowed'),
    ]
)]
#[OA\Patch(
    path: '/api/orders/{uuid}/items/{id}/status',
    operationId: 'orders.items.updateStatus',
    tags: ['OrderItems'],
    summary: 'Printer (own) / admin: advance item status.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['status'],
        properties: [new OA\Property(property: 'status', type: 'string', enum: ['received', 'printing', 'printed', 'handed_off'])]
    )),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/OrderItem')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Patch(
    path: '/api/orders/{uuid}/items/{id}/cancel',
    operationId: 'orders.items.cancel',
    tags: ['OrderItems'],
    summary: 'Customer (own, before printed) / printer (own) / admin.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(content: new OA\JsonContent(
        required: ['reason'],
        properties: [new OA\Property(property: 'reason', type: 'string', maxLength: 500)]
    )),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/OrderItem')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not allowed / item already printed'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Patch(
    path: '/api/admin/orders/{uuid}/items/{id}/printer',
    operationId: 'admin.orders.items.reassignPrinter',
    tags: ['OrderItems'],
    summary: 'Admin: reassign printer (only when item status=pending).',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['printer_provider_id'],
        properties: [new OA\Property(property: 'printer_provider_id', type: 'string', format: 'uuid')]
    )),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/OrderItem')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Item not in pending status'),
    ]
)]
// ─── Delivery Companies ────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/delivery-companies',
    operationId: 'deliveryCompanies.index',
    tags: ['DeliveryCompanies'],
    summary: 'Public: list delivery companies.',
    parameters: [
        new OA\Parameter(name: 'coverage_zone', in: 'query', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/DeliveryCompany')))]
)]
#[OA\Get(
    path: '/api/delivery-companies/{id}',
    operationId: 'deliveryCompanies.show',
    tags: ['DeliveryCompanies'],
    summary: 'Public: single delivery company.',
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DeliveryCompany')),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/admin/delivery-companies',
    operationId: 'admin.deliveryCompanies.store',
    tags: ['DeliveryCompanies'],
    summary: 'Admin: create delivery company.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreDeliveryCompanyRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/DeliveryCompany')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/admin/delivery-companies/{id}',
    operationId: 'admin.deliveryCompanies.update',
    tags: ['DeliveryCompanies'],
    summary: 'Admin: update delivery company.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateDeliveryCompanyRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DeliveryCompany')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/admin/delivery-companies/{id}',
    operationId: 'admin.deliveryCompanies.destroy',
    tags: ['DeliveryCompanies'],
    summary: 'Admin: delete. 409 if shipments reference.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 409, description: 'Shipments reference this company'),
    ]
)]
#[OA\Schema(
    schema: 'StoreDeliveryCompanyRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 180),
        new OA\Property(property: 'coverage_zones', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'tracking_url_pattern', type: 'string', format: 'uri', nullable: true, description: 'Must contain {tracking_number}'),
    ]
)]
#[OA\Schema(
    schema: 'UpdateDeliveryCompanyRequest',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 180),
        new OA\Property(property: 'coverage_zones', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'tracking_url_pattern', type: 'string', format: 'uri', nullable: true),
    ]
)]
// ─── Shipments ─────────────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/shipments',
    operationId: 'shipments.index',
    tags: ['Shipments'],
    summary: 'Printer (own) / admin: list shipments.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'shipped', 'delivered'])),
        new OA\Parameter(name: 'order_id', in: 'query', schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Shipment'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not printer/admin'),
    ]
)]
#[OA\Get(
    path: '/api/shipments/{id}',
    operationId: 'shipments.show',
    tags: ['Shipments'],
    summary: 'Customer (own via order) / printer (own) / admin.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Shipment')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not allowed'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/orders/{uuid}/shipments',
    operationId: 'orders.shipments.store',
    tags: ['Shipments'],
    summary: 'Printer (own) / admin: create shipment.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreShipmentRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Shipment')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not allowed'),
        new OA\Response(response: 404, description: 'Order not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/shipments/{id}',
    operationId: 'shipments.update',
    tags: ['Shipments'],
    summary: 'Printer (own) / admin: update shipment. Fires OrderShipped / OrderDelivered.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateShipmentRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Shipment')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/shipments/{id}',
    operationId: 'shipments.destroy',
    tags: ['Shipments'],
    summary: 'Admin: hard-delete shipment.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Schema(
    schema: 'StoreShipmentRequest',
    required: ['printer_provider_id', 'covered_item_ids'],
    properties: [
        new OA\Property(property: 'printer_provider_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'delivery_company_id', type: 'integer', nullable: true),
        new OA\Property(property: 'tracking_number', type: 'string', maxLength: 120, nullable: true),
        new OA\Property(property: 'covered_item_ids', type: 'array', items: new OA\Items(type: 'integer'), minItems: 1),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'shipped', 'delivered'], default: 'pending'),
    ]
)]
#[OA\Schema(
    schema: 'UpdateShipmentRequest',
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'shipped', 'delivered']),
        new OA\Property(property: 'tracking_number', type: 'string', maxLength: 120, nullable: true),
        new OA\Property(property: 'shipped_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'delivered_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
// ─── Payments ──────────────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/me/payments',
    operationId: 'me.payments.index',
    tags: ['Payments'],
    summary: 'Customer: own payments.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['pending', 'confirmed', 'rejected'])),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Payment'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not customer'),
    ]
)]
#[OA\Get(
    path: '/api/payments/{uuid}',
    operationId: 'payments.show',
    tags: ['Payments'],
    summary: 'Customer (own) / admin.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Payment')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not allowed'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/orders/{uuid}/payments',
    operationId: 'orders.payments.store',
    tags: ['Payments'],
    summary: 'Customer (own) / admin: create payment for an order.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StorePaymentRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Payment')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not allowed'),
        new OA\Response(response: 404, description: 'Order not found'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/payments/{uuid}/confirm',
    operationId: 'payments.confirm',
    tags: ['Payments'],
    summary: 'Admin: confirm payment. Fires PaymentConfirmed.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Payment')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Patch(
    path: '/api/payments/{uuid}/reject',
    operationId: 'payments.reject',
    tags: ['Payments'],
    summary: 'Admin: reject payment.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['reason'],
        properties: [new OA\Property(property: 'reason', type: 'string', maxLength: 500)]
    )),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Payment')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Delete(
    path: '/api/admin/payments/{uuid}',
    operationId: 'admin.payments.destroy',
    tags: ['Payments'],
    summary: 'Admin: soft-delete payment.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not admin'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Schema(
    schema: 'StorePaymentRequest',
    required: ['method'],
    properties: [
        new OA\Property(property: 'method', type: 'string', enum: ['cash_on_delivery', 'bank_transfer', 'card']),
    ]
)]
// ─── Media ─────────────────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/media/{id}',
    operationId: 'media.show',
    tags: ['Media'],
    summary: 'Show media (depends on owner type).',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Media')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not allowed'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/designs/{uuid}/media',
    operationId: 'designs.media.store',
    tags: ['Media'],
    summary: 'Designer (own) / admin: upload design media (multipart).',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['file', 'collection_name'],
                properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    new OA\Property(property: 'collection_name', type: 'string', enum: ['mockup', 'print_file', 'attachment']),
                ]
            )
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Media')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not the owner'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Post(
    path: '/api/payments/{uuid}/media',
    operationId: 'payments.media.store',
    tags: ['Media'],
    summary: 'Customer (own) / admin: upload payment proof.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['file'],
                properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    new OA\Property(property: 'collection_name', type: 'string', enum: ['payment_proof'], default: 'payment_proof'),
                ]
            )
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Media')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not allowed'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/media/{id}',
    operationId: 'media.destroy',
    tags: ['Media'],
    summary: 'Designer (own) / printer (own) / admin: delete media.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Not allowed'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
// ─── Notifications ─────────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/me/notifications',
    operationId: 'me.notifications.index',
    tags: ['Notifications'],
    summary: 'List own notifications.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'unread', in: 'query', schema: new OA\Schema(type: 'boolean')),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Notification'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/me/notifications/unread-count',
    operationId: 'me.notifications.unreadCount',
    tags: ['Notifications'],
    summary: 'Get unread count badge.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/UnreadCount')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Patch(
    path: '/api/me/notifications/{id}/read',
    operationId: 'me.notifications.markRead',
    tags: ['Notifications'],
    summary: 'Mark a single notification read.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Notification')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/me/notifications/mark-all-read',
    operationId: 'me.notifications.markAllRead',
    tags: ['Notifications'],
    summary: 'Mark all own notifications read.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(properties: [new OA\Property(property: 'marked', type: 'integer')])),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Post(
    path: '/api/admin/notifications',
    operationId: 'admin.notifications.store',
    tags: ['Notifications'],
    summary: 'Admin broadcasts or targets a notification.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreNotificationRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Notification')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — admin only'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/me/notifications/{id}',
    operationId: 'me.notifications.destroy',
    tags: ['Notifications'],
    summary: 'Delete a single own notification.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'))],
    responses: [
        new OA\Response(response: 204, description: 'No content'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
// ─── Settings ──────────────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/settings',
    operationId: 'settings.index',
    tags: ['Settings'],
    summary: 'List public platform settings (filtered by namespace).',
    parameters: [new OA\Parameter(name: 'namespace', in: 'query', required: false, schema: new OA\Schema(type: 'string'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Setting'))),
    ]
)]
#[OA\Get(
    path: '/api/settings/{id}',
    operationId: 'settings.show',
    tags: ['Settings'],
    summary: 'Show a single setting (public if namespace is public, else admin only).',
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Setting')),
        new OA\Response(response: 403, description: 'Forbidden — namespace is admin-only'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/admin/settings',
    operationId: 'admin.settings.store',
    tags: ['Settings'],
    summary: 'Create a platform setting.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreSettingRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Created', content: new OA\JsonContent(ref: '#/components/schemas/Setting')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — admin only'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/admin/settings/{id}',
    operationId: 'admin.settings.update',
    tags: ['Settings'],
    summary: 'Update a platform setting.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateSettingRequest')),
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/Setting')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — admin only'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
// ─── Admin: Dashboard, Audit, Integrity ────────────────────────────────────────
#[OA\Get(
    path: '/api/admin/dashboard',
    operationId: 'admin.dashboard',
    tags: ['Admin'],
    summary: 'Platform overview KPIs (see Report #1, 60s cache).',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PlatformOverview')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — admin only'),
    ]
)]
#[OA\Get(
    path: '/api/admin/audit/deleted',
    operationId: 'admin.audit.deleted',
    tags: ['Admin'],
    summary: 'Soft-delete audit trail.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'entity', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/AuditEntry'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — admin only'),
    ]
)]
#[OA\Get(
    path: '/api/admin/integrity/profile-mismatches',
    operationId: 'admin.integrity.profileMismatches',
    tags: ['Admin'],
    summary: 'Users whose role is designer/printer_provider but lack a profile row.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/User'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — admin only'),
    ]
)]
#[OA\Get(
    path: '/api/admin/integrity/orphaned-media',
    operationId: 'admin.integrity.orphanedMedia',
    tags: ['Admin'],
    summary: 'Media rows whose owner is missing or soft-deleted.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Media'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — admin only'),
    ]
)]
#[OA\Get(
    path: '/api/admin/integrity/items-without-shipment',
    operationId: 'admin.integrity.itemsWithoutShipment',
    tags: ['Admin'],
    summary: 'Order items with status handed_off lacking a shipment.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderItem'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — admin only'),
    ]
)]
#[OA\Get(
    path: '/api/admin/integrity/stuck-cart-items',
    operationId: 'admin.integrity.stuckCartItems',
    tags: ['Admin'],
    summary: 'Cart rows pointing to inactive product variants.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/CartItem'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — admin only'),
    ]
)]
// ─── Reports ───────────────────────────────────────────────────────────────────
#[OA\Get(
    path: '/api/reports/admin/overview',
    operationId: 'reports.admin.overview',
    tags: ['Reports'],
    summary: 'Platform KPI snapshot (60s cache).',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'format', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['json', 'csv'], default: 'json')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PlatformOverview')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — admin only'),
    ]
)]
#[OA\Get(
    path: '/api/reports/admin/revenue-by-day',
    operationId: 'reports.admin.revenueByDay',
    tags: ['Reports'],
    summary: 'Daily revenue series.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'payment_method', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'country', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'format', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['json', 'csv'], default: 'json')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/RevenueByDayRow'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/reports/printer/work-queue',
    operationId: 'reports.printer.workQueue',
    tags: ['Reports'],
    summary: 'Order items needing action for the authenticated printer.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderItem'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — printer only'),
    ]
)]
#[OA\Get(
    path: '/api/reports/designer/dashboard',
    operationId: 'reports.designer.dashboard',
    tags: ['Reports'],
    summary: 'Designer KPIs (60s cache).',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DesignerDashboard')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — designer only'),
    ]
)]
#[OA\Get(
    path: '/api/reports/admin/revenue-by-designer',
    operationId: 'reports.admin.revenueByDesigner',
    tags: ['Reports'],
    summary: 'Top designers by revenue.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, maximum: 100)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/RevenueByDesignerRow'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/reports/admin/revenue-by-printer',
    operationId: 'reports.admin.revenueByPrinter',
    tags: ['Reports'],
    summary: 'Top printers by revenue.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, maximum: 100)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/RevenueByPrinterRow'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/reports/ops/stuck-payments',
    operationId: 'reports.ops.stuckPayments',
    tags: ['Reports'],
    summary: 'Payments pending past threshold.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'min_age_days', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 3))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Payment'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/reports/admin/customer-ltv',
    operationId: 'reports.admin.customerLtv',
    tags: ['Reports'],
    summary: 'Customer lifetime value leaderboard.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 100, maximum: 500)),
        new OA\Parameter(name: 'format', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['json', 'csv'], default: 'json')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/CustomerLtvRow'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/reports/designer/payout',
    operationId: 'reports.designer.payout',
    tags: ['Reports'],
    summary: 'Designer payout statement.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'format', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['json', 'csv'], default: 'json')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/DesignerPayoutStatement')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — designer only'),
    ]
)]
#[OA\Get(
    path: '/api/reports/printer/payout',
    operationId: 'reports.printer.payout',
    tags: ['Reports'],
    summary: 'Printer payout statement.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'format', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['json', 'csv'], default: 'json')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(ref: '#/components/schemas/PrinterPayoutStatement')),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — printer only'),
    ]
)]
#[OA\Get(
    path: '/api/reports/ops/cart-abandonment',
    operationId: 'reports.ops.cartAbandonment',
    tags: ['Reports'],
    summary: 'Cart rows inactive past threshold.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'min_age_days', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 7))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/CartItem'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/reports/ops/stuck-shipments',
    operationId: 'reports.ops.stuckShipments',
    tags: ['Reports'],
    summary: 'Shipments pending/shipped past threshold.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'min_age_days', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 7))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Shipment'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/me/orders',
    operationId: 'reports.me.orders',
    tags: ['Reports'],
    summary: 'Alias of GET /api/orders filtered to the authenticated customer.',
    security: [['sanctum' => []]],
    parameters: [new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1))],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Order'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/me/shipments/active',
    operationId: 'reports.me.shipmentsActive',
    tags: ['Reports'],
    summary: 'Active shipments for the authenticated customer.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Shipment'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/reports/admin/top-designs',
    operationId: 'reports.admin.topDesigns',
    tags: ['Reports'],
    summary: 'Top designs by revenue.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 25, maximum: 100)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/DesignRevenueRow'))),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
// ─── Additional Schemas (Task 12) ──────────────────────────────────────────────
#[OA\Schema(
    schema: 'StoreNotificationRequest',
    required: ['user_id', 'type', 'data'],
    properties: [
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', nullable: true, description: 'If null, broadcast to all users'),
        new OA\Property(property: 'type', type: 'string', maxLength: 80),
        new OA\Property(property: 'data', type: 'object', additionalProperties: new OA\AdditionalProperties),
    ]
)]
#[OA\Schema(
    schema: 'StoreSettingRequest',
    required: ['namespace', 'key', 'value'],
    properties: [
        new OA\Property(property: 'namespace', type: 'string', maxLength: 80),
        new OA\Property(property: 'key', type: 'string', maxLength: 80),
        new OA\Property(property: 'value', type: 'object', additionalProperties: new OA\AdditionalProperties),
        new OA\Property(property: 'is_public', type: 'boolean', default: false),
    ]
)]
#[OA\Schema(
    schema: 'UpdateSettingRequest',
    properties: [
        new OA\Property(property: 'value', type: 'object', additionalProperties: new OA\AdditionalProperties),
        new OA\Property(property: 'is_public', type: 'boolean'),
    ]
)]
#[OA\Schema(
    schema: 'AuditEntry',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'entity_type', type: 'string'),
        new OA\Property(property: 'entity_id', type: 'string'),
        new OA\Property(property: 'action', type: 'string', enum: ['create', 'update', 'delete', 'restore']),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'RevenueByDayRow',
    properties: [
        new OA\Property(property: 'date', type: 'string', format: 'date'),
        new OA\Property(property: 'revenue', type: 'number', format: 'float'),
        new OA\Property(property: 'order_count', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'RevenueByDesignerRow',
    properties: [
        new OA\Property(property: 'designer_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'revenue', type: 'number', format: 'float'),
        new OA\Property(property: 'order_count', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'RevenueByPrinterRow',
    properties: [
        new OA\Property(property: 'printer_provider_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'revenue', type: 'number', format: 'float'),
        new OA\Property(property: 'shipment_count', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'CustomerLtvRow',
    properties: [
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'total_spent', type: 'number', format: 'float'),
        new OA\Property(property: 'order_count', type: 'integer'),
        new OA\Property(property: 'first_order_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'last_order_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'DesignerDashboard',
    properties: [
        new OA\Property(property: 'design_count', type: 'integer'),
        new OA\Property(property: 'published_count', type: 'integer'),
        new OA\Property(property: 'total_revenue', type: 'number', format: 'float'),
        new OA\Property(property: 'pending_payout', type: 'number', format: 'float'),
    ]
)]
#[OA\Schema(
    schema: 'DesignerPayoutStatement',
    properties: [
        new OA\Property(property: 'designer_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'from', type: 'string', format: 'date'),
        new OA\Property(property: 'to', type: 'string', format: 'date'),
        new OA\Property(property: 'total_payout', type: 'number', format: 'float'),
        new OA\Property(property: 'lines', type: 'array', items: new OA\Items(type: 'object', additionalProperties: new OA\AdditionalProperties)),
    ]
)]
#[OA\Schema(
    schema: 'PrinterPayoutStatement',
    properties: [
        new OA\Property(property: 'printer_provider_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'from', type: 'string', format: 'date'),
        new OA\Property(property: 'to', type: 'string', format: 'date'),
        new OA\Property(property: 'total_payout', type: 'number', format: 'float'),
        new OA\Property(property: 'lines', type: 'array', items: new OA\Items(type: 'object', additionalProperties: new OA\AdditionalProperties)),
    ]
)]
#[OA\Schema(
    schema: 'DesignRevenueRow',
    properties: [
        new OA\Property(property: 'design_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'revenue', type: 'number', format: 'float'),
        new OA\Property(property: 'order_count', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'PlatformOverview',
    description: 'Aggregated platform KPIs returned by the admin dashboard and admin overview report.',
    properties: [
        new OA\Property(property: 'users_total', type: 'integer'),
        new OA\Property(property: 'designs_total', type: 'integer'),
        new OA\Property(property: 'orders_total', type: 'integer'),
        new OA\Property(property: 'revenue_total', type: 'number', format: 'float'),
        new OA\Property(property: 'orders_pending', type: 'integer'),
        new OA\Property(property: 'shipments_in_transit', type: 'integer'),
        new OA\Property(property: 'generated_at', type: 'string', format: 'date-time'),
    ]
)]
final class OpenApi {}
