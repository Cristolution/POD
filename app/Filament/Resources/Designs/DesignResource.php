<?php

declare(strict_types=1);

namespace App\Filament\Resources\Designs;

use App\Filament\Resources\Designs\Pages\CreateDesign;
use App\Filament\Resources\Designs\Pages\EditDesign;
use App\Filament\Resources\Designs\Pages\ListDesigns;
use App\Filament\Resources\Designs\Pages\ViewDesign;
use App\Filament\Resources\Designs\Schemas\DesignForm;
use App\Filament\Resources\Designs\Schemas\DesignInfolist;
use App\Filament\Resources\Designs\Tables\DesignsTable;
use App\Models\Design;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DesignResource extends Resource
{
    protected static ?string $model = Design::class;

    protected static ?string $modelLabel = 'Design';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return DesignForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DesignInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DesignsTable::configure($table);
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
            'index' => ListDesigns::route('/'),
            'create' => CreateDesign::route('/create'),
            'view' => ViewDesign::route('/{record}'),
            'edit' => EditDesign::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
