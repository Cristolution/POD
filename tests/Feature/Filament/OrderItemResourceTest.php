<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\OrderItems\OrderItemResource;
use App\Filament\Resources\OrderItems\Pages\ListOrderItems;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderItemResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_order_items(): void
    {
        OrderItem::factory()->count(3)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListOrderItems::class)
            ->assertSuccessful();
    }

    public function test_order_item_resource_is_read_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertFalse(OrderItemResource::canCreate());
    }
}
