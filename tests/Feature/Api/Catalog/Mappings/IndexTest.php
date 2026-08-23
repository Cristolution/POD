<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Mappings;

use App\Models\Design;
use Database\Factories\DesignProductMappingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_can_list_mappings(): void
    {
        DesignProductMappingFactory::new()->count(3)->create();

        $this->getJson('/api/mappings')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_can_be_filtered_by_design_id(): void
    {
        $designs = Design::factory()->count(2)->create();
        DesignProductMappingFactory::new()->count(2)->for($designs[0], 'design')->create();
        DesignProductMappingFactory::new()->count(3)->for($designs[1], 'design')->create();

        $this->getJson("/api/mappings?design_id={$designs[0]->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
