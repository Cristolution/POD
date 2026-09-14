<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ConfirmDeleteMeAction
{
    /**
     * Soft-delete the user identified by the signed URL, revoke all of their
     * Sanctum tokens, and log them out of the current session. Called from
     * the signed `/me/delete/confirm/{id}` route — the signature has already
     * been verified by the `signed` middleware by the time we get here.
     */
    public function execute(string $id): void
    {
        /** @var User|null $user */
        $user = User::find($id);

        if ($user === null || $user->trashed()) {
            // Either the user never existed, or was already deleted. Either
            // way, nothing to do — fall through so the caller still redirects
            // to the landing page.
            return;
        }

        $user->delete();

        // Revoke Sanctum tokens (mobile/API clients).
        $user->tokens()->delete();

        // Log out of the web session if the deleted user happens to be the
        // currently authenticated session user (e.g. they clicked the link
        // while still logged in elsewhere).
        if (Auth::check() && Auth::id() === $user->getKey()) {
            Session::flush();
            Session::regenerate();
        }
    }
}
