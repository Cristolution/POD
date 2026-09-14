<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\User\ConfirmDeleteMeAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ConfirmDeleteMeController extends Controller
{
    public function __construct(
        private readonly ConfirmDeleteMeAction $confirmDeleteMe,
    ) {}

    public function __invoke(string $id): RedirectResponse
    {
        $this->confirmDeleteMe->execute($id);

        return redirect()->route('account.deleted');
    }
}
