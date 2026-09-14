<?php

declare(strict_types=1);

namespace App\Filament\Pages\Approvals;

use App\Actions\Payment\ConfirmPaymentAction;
use App\Actions\Payment\RejectPaymentAction;
use App\Models\Payment;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Pending payments queue — admin confirm/reject without going through the
 * generic Edit form on the Payments resource.
 *
 * Reuses {@see ConfirmPaymentAction} / {@see RejectPaymentAction} so the
 * status mutations, audit trail, and customer notifications all flow
 * through the same code path the rest of the app already uses.
 */
class PendingPaymentsPage extends Page
{
    protected string $view = 'filament.pages.approvals.payments';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Pending payments';

    protected static ?string $title = 'Pending payments';

    protected static ?string $slug = 'approvals-payments';

    protected static ?int $navigationSort = 2;

    /**
     * Eagerly loaded on mount and reloaded after every action.
     *
     * @var Collection<int, Payment>
     */
    public Collection $payments;

    public function mount(): void
    {
        $this->loadQueue();
    }

    public function confirmPayment(string $paymentId): void
    {
        $payment = Payment::findOrFail($paymentId);

        if (! $payment->isPending()) {
            Notification::make()
                ->title('Payment no longer pending')
                ->body('Another admin already actioned this payment.')
                ->warning()
                ->send();
            $this->loadQueue();

            return;
        }

        $admin = $this->currentAdmin();
        app(ConfirmPaymentAction::class)->execute($payment, $admin);

        Notification::make()->title('Payment confirmed')->success()->send();
        $this->loadQueue();
    }

    public function rejectPayment(string $paymentId): void
    {
        $payment = Payment::findOrFail($paymentId);

        if (! $payment->isPending()) {
            Notification::make()
                ->title('Payment no longer pending')
                ->body('Another admin already actioned this payment.')
                ->warning()
                ->send();
            $this->loadQueue();

            return;
        }

        $admin = $this->currentAdmin();
        app(RejectPaymentAction::class)->execute($payment, $admin);

        Notification::make()->title('Payment rejected')->success()->send();
        $this->loadQueue();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openPaymentsResource')
                ->label('All payments')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('gray')
                ->url(fn (): string => url('/admin/payments')),
        ];
    }

    private function loadQueue(): void
    {
        $this->payments = Payment::query()
            ->where('status', 'pending')
            ->with(['order.customer'])
            ->latest('created_at')
            ->get();
    }

    private function currentAdmin(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
