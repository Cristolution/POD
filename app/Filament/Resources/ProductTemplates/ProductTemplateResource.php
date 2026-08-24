<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductTemplates;

use App\Filament\Resources\ProductTemplates\Pages\CreateProductTemplate;
use App\Filament\Resources\ProductTemplates\Pages\EditProductTemplate;
use App\Filament\Resources\ProductTemplates\Pages\ListProductTemplates;
use App\Filament\Resources\ProductTemplates\Pages\ViewProductTemplate;
use App\Filament\Resources\ProductTemplates\Schemas\ProductTemplateForm;
use App\Filament\Resources\ProductTemplates\Schemas\ProductTemplateInfolist;
use App\Filament\Resources\ProductTemplates\Tables\ProductTemplatesTable;
use App\Models\ProductTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProductTemplateResource extends Resource
{
    protected static ?string $model = ProductTemplate::class;

    protected static ?string $modelLabel = 'Product template';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return ProductTemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductTemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductTemplatesTable::configure($table);
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
            'index' => ListProductTemplates::route('/'),
            'create' => CreateProductTemplate::route('/create'),
            'view' => ViewProductTemplate::route('/{record}'),
            'edit' => EditProductTemplate::route('/{record}/edit'),
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
