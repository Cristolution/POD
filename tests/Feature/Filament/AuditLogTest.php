<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Spy on the `audit` log channel specifically so we can assert that
     * the observer forwarded every admin CRUD event into it.
     */
    private function spyAuditChannel(): Mockery\MockInterface
    {
        $logSpy = Log::spy();
        $channelSpy = Mockery::spy();

        $logSpy->shouldReceive('channel')->with('audit')->andReturn($channelSpy);

        return $channelSpy;
    }

    public function test_admin_update_writes_to_audit_log(): void
    {
        $audit = $this->spyAuditChannel();
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'customer', 'name' => 'Original']);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $target->getKey()])
            ->fillForm([
                'name' => 'Updated Name',
                'email' => $target->email,
                'role' => 'customer',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $audit->shouldHaveReceived('info')
            ->withArgs(
                fn (string $message, array $context = []): bool => str_contains($message, 'admin.updated')
                    && ($context['entity'] ?? null) === User::class
                    && ($context['id'] ?? null) === $target->getKey()
                    && ($context['user_id'] ?? null) === $admin->getKey(),
            )
            ->atLeast()->once();
    }

    public function test_admin_creating_user_writes_audit(): void
    {
        $audit = $this->spyAuditChannel();
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'New Customer',
                'email' => 'new@example.com',
                'password' => 'SecretPass1!',
                'passwordConfirmation' => 'SecretPass1!',
                'role' => 'customer',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $audit->shouldHaveReceived('info')
            ->withArgs(
                fn (string $message, array $context = []): bool => str_contains($message, 'admin.created')
                    && ($context['entity'] ?? null) === User::class
                    && ($context['user_id'] ?? null) === $admin->getKey(),
            )
            ->atLeast()->once();
    }

    public function test_non_admin_update_does_not_write_audit(): void
    {
        $audit = $this->spyAuditChannel();
        $customer = User::factory()->create(['role' => 'customer']);
        $target = User::factory()->create(['role' => 'customer', 'name' => 'Original']);

        // Bypass the Filament UI: the observer must still refuse to log
        // because the actor is not an admin, regardless of how the change
        // reached the model.
        $this->actingAs($customer);
        $target->update(['name' => 'Should Not Persist']);

        $audit->shouldNotHaveReceived('info');
    }

    public function test_non_admin_cannot_reach_admin_pages(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get('/admin/users')->assertForbidden();
    }
}
