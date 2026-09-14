<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeleteAccountConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $confirmationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->user->email],
            subject: __('account_deletion_mail_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.delete-account-confirmation',
            with: [
                'name' => $this->user->name,
                'confirmationUrl' => $this->confirmationUrl,
            ],
        );
    }
}
