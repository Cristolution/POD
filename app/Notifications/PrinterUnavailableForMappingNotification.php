<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\DesignProductMapping;
use App\Models\PrinterProviderProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrinterUnavailableForMappingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly DesignProductMapping $mapping,
        public readonly PrinterProviderProfile $printer,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $printerName = $this->printer->company_name
            ?? $this->printer->user?->name
            ?? 'your previous print partner';

        return (new MailMessage)
            ->subject("Print partner {$printerName} is no longer available")
            ->line("Print partner \"{$printerName}\" is no longer fulfilling orders for your design.")
            ->line('Your design ↔ product mapping has been preserved — please pick a new printer so it can stay available for sale.')
            ->action('Reassign printer', url("/dashboard/designs/{$this->mapping->design_id}/mappings/{$this->mapping->id}/edit"))
            ->line('If you do not reassign, the mapping will be hidden from the catalog.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'event' => 'mapping.printer_unavailable',
            'mapping_id' => $this->mapping->id,
            'design_id' => $this->mapping->design_id,
            'product_template_id' => $this->mapping->product_template_id,
            'printer_id' => $this->printer->id,
            'printer_name' => $this->printer->company_name ?? $this->printer->user?->name,
            'message' => 'Print partner is no longer fulfilling this mapping. Please reassign.',
        ];
    }
}
