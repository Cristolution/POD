<?php

namespace App\Filament\Resources\DesignProductMappings\Pages;

use App\Filament\Resources\DesignProductMappings\DesignProductMappingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDesignProductMapping extends ViewRecord
{
    protected static string $resource = DesignProductMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
