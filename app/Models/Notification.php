<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;

/**
 * App Notification model.
 *
 * Extends Laravel's DatabaseNotification so we get:
 *   - markAsRead() / markAsUnread()
 *   - Scope queries for unread/notificationFor
 *   - UUID primary key (matches the notifications table)
 *
 * The `type` column is intentionally NOT a closed enum — new notification
 * types ship as features are added.
 */
class Notification extends BaseDatabaseNotification
{
    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }
}
