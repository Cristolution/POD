<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Catalog\Designs;

use App\Models\Category;
use App\Models\Design;
use App\Models\User;
use Database\Factories\DesignerProfileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_only_sees_published_designs(): void
    {
        $designer = DesignerProfileFactory::new()
            ->for(User::factory()->designer(), 'user')
            ->create();

        Design::factory()->for($designer, 'designer')->create(['status' => 'published', 'title' => 'Public One']);
        Design::factory()->for($designer, 'designer')->create(['status' => 'draft', 'title' => 'Hidden Draft']);
        Design::factory()->for($designer, 'designer')->create(['status' => 'archived', 'title' => 'Hidden Archived']);

        $this->getJson('/api/designs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Public One');
    }

    public function test_designer_sees_published_plus_own_non_published(): void
    {
        $ownProfile = DesignerProfileFactory::new()
            ->for(User::factory()->designer(), 'user')
            ->create();
        $otherProfile = DesignerProfileFactory::new()
            ->for(User::factory()->designer(), 'user')
            ->create();

        Design::factory()->for($ownProfile, 'designer')->create(['status' => 'published', 'title' => 'Mine Published']);
        Design::factory()->for($ownProfile, 'designer')->create(['status' => 'draft', 'title' => 'Mine Draft']);
        Design::factory()->for($otherProfile, 'designer')->create(['status' => 'published', 'title' => 'Other Published']);
        Design::factory()->for($otherProfile, 'designer')->create(['status' => 'draft', 'title' => 'Other Draft Hidden']);

        $this->actingAs($ownProfile->user, 'sanctum')
            ->getJson('/api/designs')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_supports_filters(): void
    {
        $profile = DesignerProfileFactory::new()
            ->for(User::factory()->designer(), 'user')
            ->create();

        $categoryA = Category::factory()->create(['name' => 'T-shirts']);
        $categoryB = Category::factory()->create(['name' => 'Posters']);

        Design::factory()->for($profile, 'designer')->create([
            'category_id' => $categoryA->id,
            'status' => 'published',
            'title' => 'Red shirt',
        ]);
        Design::factory()->for($profile, 'designer')->create([
            'category_id' => $categoryB->id,
            'status' => 'published',
            'title' => 'Blue poster',
        ]);

        $this->getJson('/api/designs?category_id='.$categoryA->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Red shirt');

        $this->getJson('/api/designs?search=poster')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Blue poster');

        $this->getJson('/api/designs?status=published&designer_id='.$profile->id)
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
