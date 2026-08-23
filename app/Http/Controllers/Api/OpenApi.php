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
final class OpenApi {}
