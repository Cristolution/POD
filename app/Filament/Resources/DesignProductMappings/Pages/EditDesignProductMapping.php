<?php

namespace App\Filament\Resources\DesignProductMappings\Pages;

use App\Filament\Resources\DesignProductMappings\DesignProductMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDesignProductMapping extends EditRecord
{
    protected static string $resource = DesignProductMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
