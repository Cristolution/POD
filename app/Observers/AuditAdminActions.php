<?php

declare(strict_types=1);

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Records every create/update/delete/restore performed by an authenticated
 * admin to the dedicated `audit` log channel, so admin actions can be
 * reviewed independently of the application runtime log.
 */
final class AuditAdminActions
{
    public function created(Model $model): void
    {
        $this->log('created', $model);
    }

    public function updated(Model $model): void
    {
        $this->log('updated', $model, $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model);
    }

    public function restored(Model $model): void
    {
        $this->log('restored', $model);
    }

    private function log(string $verb, Model $model, array $changes = []): void
    {
        $user = auth()->user();
        if ($user === null || ! $user->isAdmin()) {
            return;  // only audit admin actions
        }

        Log::channel('audit')->info("admin.{$verb}", [
            'entity' => $model::class,
            'id' => $model->getKey(),
            'user_id' => $user->getKey(),
            'changes' => $changes,
            'ip' => request()?->ip(),
        ]);
    }
}
