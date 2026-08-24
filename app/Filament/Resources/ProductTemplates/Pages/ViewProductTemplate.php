<?php

namespace App\Filament\Resources\ProductTemplates\Pages;

use App\Filament\Resources\ProductTemplates\ProductTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProductTemplate extends ViewRecord
{
    protected static string $resource = ProductTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
