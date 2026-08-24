<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Collection;

class ProfileMismatchesAction
{
    /**
     * Find users whose role expects a profile but none exists.
     *
     * @return array{
     *     designers_without_profile: Collection<int, User>,
     *     printers_without_profile: Collection<int, User>,
     * }
     */
    public function execute(): array
    {
        return [
            'designers_without_profile' => User::where('role', 'designer')
                ->whereDoesntHave('designerProfile')
                ->select('id', 'name', 'email')
                ->get(),
            'printers_without_profile' => User::where('role', 'printer_provider')
                ->whereDoesntHave('printerProviderProfile')
                ->select('id', 'name', 'email')
                ->get(),
        ];
    }
}
