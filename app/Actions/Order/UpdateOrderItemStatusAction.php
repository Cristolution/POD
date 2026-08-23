<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Models\OrderItem;

class UpdateOrderItemStatusAction
{
    /**
     * Allowed status transitions, aligned with the OrderItem enum:
     *   pending → received → printing → printed → handed_off
     * Cancelled is reachable from every non-terminal step.
     *
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        'pending' => ['received', 'cancelled'],
        'received' => ['printing', 'cancelled'],
        'printing' => ['printed', 'cancelled'],
        'printed' => ['handed_off', 'cancelled'],
        'handed_off' => ['cancelled'],
        'cancelled' => [],
    ];

    public function execute(OrderItem $item, string $newStatus): OrderItem
    {
        $current = $item->status;

        abort_unless(
            in_array($newStatus, self::TRANSITIONS[$current] ?? [], true),
            409,
            "Invalid status transition from '{$current}' to '{$newStatus}'."
        );

        $item->status = $newStatus;
        $item->save();

        return $item->refresh();
    }
}
