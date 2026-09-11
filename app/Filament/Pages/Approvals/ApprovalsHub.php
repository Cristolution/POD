<?php

declare(strict_types=1);

namespace App\Filament\Pages\Approvals;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

/**
 * Approvals hub — landing page for the Approvals nav group.
 *
 * Surfaces three clickable stat tiles (one per queue) that deep-link into
 * the matching sub-page. Counts are derived from the same query scopes
 * the sub-pages themselves use, so the numbers always agree with what the
 * admin sees when they drill in.
 *
 * The tiles are deliberately non-Livewire cards (just `<a>` tags) — they
 * only need to navigate, not mutate state, and a plain link keeps the
 * page snappy and the markup brutalist.
 */
class ApprovalsHub extends Page
{
    protected string $view = 'filament.pages.approvals.hub';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Approvals';

    protected static ?string $title = 'Approvals';

    protected static ?string $slug = 'approvals-hub';

    protected static ?int $navigationSort = 1;

    /**
     * Snapshot the three pending counts on mount. Re-computing on every
     * render would force N queries for no UX gain — these tiles are a
     * dashboard, not a live feed.
     *
     * @var array<string, int>
     */
    public array $counts = [];

    public function mount(): void
    {
        $this->counts = [
            'pendingPayments' => $this->pendingPaymentsCount(),
            'pendingOrders' => $this->pendingOrdersCount(),
            'unverifiedDesigners' => $this->unverifiedDesignersCount(),
        ];
    }

    private function pendingPaymentsCount(): int
    {
        return Payment::query()->where('status', 'pending')->count();
    }

    private function pendingOrdersCount(): int
    {
        return Order::query()
            ->whereIn('status', ['pending', 'paid', 'processing', 'shipped'])
            ->count();
    }

    private function unverifiedDesignersCount(): int
    {
        return User::query()
            ->where('role', 'designer')
            ->where(function (Builder $q): void {
                $q->whereDoesntHave('designerProfile')
                    ->orWhereHas('designerProfile', fn (Builder $profile) => $profile->where('is_verified', false));
            })
            ->count();
    }
}
