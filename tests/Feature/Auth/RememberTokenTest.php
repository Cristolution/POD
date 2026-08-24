<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login as FilamentLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression test for the missing remember_token column on users.
 *
 * Background: the original users migration (2026_07_29_140006) predated the
 * Authenticatable trait's reliance on a remember_token column. Filament v4's
 * Login::authenticate() runs through SessionGuard::updateSession() which
 * writes a remember_token on successful auth — sqlite (and stricter MySQL
 * configurations) reject UPDATE users SET remember_token = ? because the
 * column doesn't exist. Symptom: admin /admin/login returns 500 after a
 * successful credential check.
 *
 * Pre-fix:  Livewire::test(FilamentLogin::class)->call('authenticate') → 500.
 * Post-fix: Livewire::test(FilamentLogin::class)->call('authenticate') → 302.
 */
class RememberTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_completes_without_remember_token_error(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => bcrypt('SecretPass1!'),
        ]);

        // Filament v4 renders the admin login as a Livewire page. The Livewire
        // `authenticate` call hits SessionGuard::updateSession() which writes
        // remember_token — which throws on a users table missing that column.
        Livewire::test(FilamentLogin::class)
            ->fillForm([
                'email' => $admin->email,
                'password' => 'SecretPass1!',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertNotNull(
            Auth::user(),
            'SessionGuard::attempt() should have authenticated the admin.',
        );
    }

    public function test_users_table_has_remember_token_column(): void
    {
        // Schema-level guard: catches accidental dropColumn in a future
        // migration before the runtime regression surfaces.
        $this->assertTrue(
            Schema::hasColumn('users', 'remember_token'),
            'users table is missing remember_token column required by Authenticatable.',
        );
    }
}
