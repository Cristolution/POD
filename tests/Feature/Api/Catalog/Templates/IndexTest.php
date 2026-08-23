<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Templates;

use Database\Factories\PrinterProviderProfileFactory;
use Database\Factories\ProductTemplateFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_can_list_templates(): void
    {
        ProductTemplateFactory::new()->count(3)->create();

        $this->getJson('/api/templates')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_is_paginated(): void
    {
        ProductTemplateFactory::new()->count(30)->create();

        $response = $this->getJson('/api/templates')->assertOk();

        $this->assertCount(25, $response->json('data'));
        $this->assertSame(30, $response->json('total'));
        $this->assertSame(2, $response->json('last_page'));
    }

    public function test_index_can_be_filtered_by_printer_provider_id(): void
    {
        $profile = PrinterProviderProfileFactory::new()->create();
        $otherProfile = PrinterProviderProfileFactory::new()->create();
        ProductTemplateFactory::new()->count(2)->for($profile, 'printerProvider')->create();
        ProductTemplateFactory::new()->count(3)->for($otherProfile, 'printerProvider')->create();

        $this->getJson("/api/templates?printer_provider_id={$profile->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_can_be_filtered_by_type(): void
    {
        ProductTemplateFactory::new()->count(2)->create(['type' => 'mug']);
        ProductTemplateFactory::new()->count(3)->create(['type' => 't-shirt']);

        $this->getJson('/api/templates?type=mug')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
