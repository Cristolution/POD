<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordAction
{
    public function execute(User $user, string $current, string $new): void
    {
        abort_unless(Hash::check($current, $user->password), 422, 'Current password is incorrect.');
        $user->password = Hash::make($new);
        $user->save();
    }
}
