<?php

declare(strict_types=1);

namespace App\Filament\Resources\DesignProductMappings;

use App\Filament\Resources\DesignProductMappings\Pages\CreateDesignProductMapping;
use App\Filament\Resources\DesignProductMappings\Pages\EditDesignProductMapping;
use App\Filament\Resources\DesignProductMappings\Pages\ListDesignProductMappings;
use App\Filament\Resources\DesignProductMappings\Pages\ViewDesignProductMapping;
use App\Filament\Resources\DesignProductMappings\Schemas\DesignProductMappingForm;
use App\Filament\Resources\DesignProductMappings\Schemas\DesignProductMappingInfolist;
use App\Filament\Resources\DesignProductMappings\Tables\DesignProductMappingsTable;
use App\Models\DesignProductMapping;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DesignProductMappingResource extends Resource
{
    protected static ?string $model = DesignProductMapping::class;

    protected static ?string $modelLabel = 'Design ↔ product mapping';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return DesignProductMappingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DesignProductMappingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DesignProductMappingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDesignProductMappings::route('/'),
            'create' => CreateDesignProductMapping::route('/create'),
            'view' => ViewDesignProductMapping::route('/{record}'),
            'edit' => EditDesignProductMapping::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        // Reach trashed mapping rows AND mappings whose design has been
        // soft-deleted, so admins can restore or clean them up.
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
                'withDesign',
            ])
            ->withTrashedDesigns();
    }
}
