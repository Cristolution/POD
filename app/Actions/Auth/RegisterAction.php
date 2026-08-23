<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\TokenAbility;
use Illuminate\Support\Facades\Hash;

class RegisterAction
{
    /** @param array<string,mixed> $data
     * @return array{0: User, 1: string} */
    public function execute(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken(
            name: $data['device_name'] ?? 'web',
            abilities: TokenAbility::forRole($user->role),
        );

        return [$user, $token->plainTextToken];
    }
}
