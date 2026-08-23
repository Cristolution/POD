<?php

declare(strict_types=1);

namespace Tests\Feature\Api\DeliveryCompanies;

use App\Models\DeliveryCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_can_list_delivery_companies(): void
    {
        DeliveryCompany::factory()->count(3)->create();

        $this->getJson('/api/delivery-companies')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_pagination_works(): void
    {
        DeliveryCompany::factory()->count(30)->create();

        $response = $this->getJson('/api/delivery-companies?per_page=25')
            ->assertOk();

        $this->assertSame(30, $response->json('total'));
        $this->assertSame(25, $response->json('per_page'));
        $this->assertSame(2, $response->json('last_page'));
    }
}
