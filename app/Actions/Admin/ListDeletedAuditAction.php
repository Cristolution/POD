<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListDeletedAuditAction
{
    /**
     * List soft-deleted users for the admin audit view, newest-deletion first.
     */
    public function execute(): LengthAwarePaginator
    {
        return User::onlyTrashed()
            ->select('id', 'name', 'email', 'role', 'deleted_at')
            ->orderByDesc('deleted_at')
            ->paginate(50);
    }
}
