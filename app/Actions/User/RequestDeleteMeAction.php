<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Mail\DeleteAccountConfirmationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class RequestDeleteMeAction
{
    /**
     * Send the user a signed one-time link that, when clicked, performs the
     * soft-delete. The link expires in 30 minutes; users can request a fresh
     * link at any time.
     */
    public function execute(User $user): void
    {
        $url = URL::temporarySignedRoute(
            'me.delete.confirm',
            now()->addMinutes(30),
            ['id' => $user->getKey()],
        );

        Mail::send(new DeleteAccountConfirmationMail($user, $url));
    }
}
