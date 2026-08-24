<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users(): void
    {
        User::factory()->count(5)->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/users')->assertOk();
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'SecretPass1!',
                'passwordConfirmation' => 'SecretPass1!',
                'role' => 'customer',
                'phone' => '+15555550100',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'role' => 'customer',
        ]);
    }

    public function test_admin_cannot_demote_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $admin->id])
            ->fillForm([
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'customer',
            ])
            ->call('save')
            ->assertHasNoErrors();

        // mutateFormDataBeforeSave() should strip `role` before save,
        // leaving the admin's role intact.
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_admin_can_delete_user_via_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'customer']);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertSuccessful()
            ->callTableAction('delete', $target->id);

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_admin_can_restore_deleted_user_via_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create([
            'role' => 'customer',
            'deleted_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertSuccessful()
            ->callTableAction('restore', $target->id);

        $this->assertNull($target->fresh()->deleted_at);
    }
}
