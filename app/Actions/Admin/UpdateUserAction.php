<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUserAction
{
    /** @param array<string,mixed> $data */
    public function execute(User $target, array $data): User
    {
        if (isset($data['password']) && $data['password'] !== '') {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $target->fill($data)->save();

        return $target->refresh();
    }
}
