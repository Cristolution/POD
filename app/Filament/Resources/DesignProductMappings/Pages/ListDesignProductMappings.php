<?php

namespace App\Filament\Resources\DesignProductMappings\Pages;

use App\Filament\Resources\DesignProductMappings\DesignProductMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDesignProductMappings extends ListRecords
{
    protected static string $resource = DesignProductMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
