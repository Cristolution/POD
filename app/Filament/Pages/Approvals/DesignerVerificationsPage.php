<?php

declare(strict_types=1);

namespace App\Filament\Pages\Approvals;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Designer verifications queue — list designers whose profile is either
 * missing or marked unverified, with one-click verify action.
 *
 * Reuses the same flip-on-profile semantics as the existing
 * `toggleVerified` action on the Users table — no new model code needed.
 */
class DesignerVerificationsPage extends Page
{
    protected string $view = 'filament.pages.approvals.designers';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Designer verifications';

    protected static ?string $title = 'Designer verifications';

    protected static ?string $slug = 'approvals-designers';

    protected static ?int $navigationSort = 4;

    /**
     * @var Collection<int, User>
     */
    public Collection $designers;

    public function mount(): void
    {
        $this->loadQueue();
    }

    public function verifyDesigner(string $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->role !== 'designer') {
            Notification::make()
                ->title('Not a designer account')
                ->body('Only designer-role users can be verified here.')
                ->danger()
                ->send();

            return;
        }

        $profile = $user->designerProfile;
        if ($profile === null) {
            Notification::make()
                ->title('No designer profile yet')
                ->body('This designer has not completed their profile.')
                ->warning()
                ->send();

            return;
        }

        if ($profile->is_verified) {
            Notification::make()
                ->title('Already verified')
                ->body('This designer is already verified.')
                ->info()
                ->send();
            $this->loadQueue();

            return;
        }

        $profile->update(['is_verified' => true]);

        Notification::make()
            ->title("{$user->name} verified")
            ->success()
            ->send();
        $this->loadQueue();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openUsersResource')
                ->label('All users')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('gray')
                ->url(fn (): string => url('/admin/users')),
        ];
    }

    private function loadQueue(): void
    {
        $this->designers = User::query()
            ->where('role', 'designer')
            ->with('designerProfile')
            ->where(function (Builder $q): void {
                $q->whereDoesntHave('designerProfile')
                    ->orWhereHas('designerProfile', fn (Builder $profile) => $profile->where('is_verified', false));
            })
            ->orderBy('name')
            ->get();
    }
}
