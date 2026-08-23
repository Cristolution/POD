<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\TokenAbility;
use Illuminate\Support\Facades\Hash;

class LoginAction
{
    /** @return array{0: User, 1: string}|null Returns null on bad credentials. */
    public function execute(string $email, string $password, ?string $deviceName): ?array
    {
        $user = User::where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        $token = $user->createToken(
            name: $deviceName ?? 'web',
            abilities: TokenAbility::forRole($user->role),
        );

        return [$user, $token->plainTextToken];
    }
}
