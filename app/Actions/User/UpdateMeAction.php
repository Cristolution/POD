<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateMeAction
{
    /** @param array<string,mixed> $data */
    public function execute(User $user, array $data): User
    {
        if (isset($data['password'])) {
            abort_unless(
                Hash::check($data['current_password'] ?? '', $user->password),
                422,
                'Current password is incorrect.'
            );
            $data['password'] = Hash::make($data['password']);
            unset($data['current_password']);
        }

        $user->fill($data)->save();

        return $user->refresh();
    }
}
