<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Categories;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_can_list_categories(): void
    {
        Category::factory()->count(3)->create();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_is_paginated(): void
    {
        Category::factory()->count(30)->create();

        $response = $this->getJson('/api/categories')
            ->assertOk();

        $this->assertCount(25, $response->json('data'));
        $this->assertSame(30, $response->json('total'));
        $this->assertSame(2, $response->json('last_page'));
    }

    public function test_index_can_be_filtered_by_parent_id(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->count(2)->withParent($parent)->create();
        Category::factory()->count(3)->create();

        $this->getJson("/api/categories?parent_id={$parent->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
