<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Tags;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_can_list_tags(): void
    {
        Tag::factory()->count(3)->create();

        $this->getJson('/api/tags')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_anonymous_can_search_tags_by_name(): void
    {
        Tag::factory()->create(['name' => 'summer']);
        Tag::factory()->create(['name' => 'winter']);
        Tag::factory()->create(['name' => 'summer-sale']);

        $this->getJson('/api/tags?search=summer')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_anonymous_can_show_single_tag_with_designs_count(): void
    {
        $tag = Tag::factory()->create(['name' => 'limited']);

        $this->getJson("/api/tags/{$tag->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $tag->id)
            ->assertJsonPath('data.name', 'limited')
            ->assertJsonPath('data.designs_count', 0);
    }
}
