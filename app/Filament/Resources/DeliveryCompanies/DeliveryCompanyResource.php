<?php

declare(strict_types=1);

namespace App\Filament\Resources\DeliveryCompanies;

use App\Filament\Resources\DeliveryCompanies\Pages\CreateDeliveryCompany;
use App\Filament\Resources\DeliveryCompanies\Pages\EditDeliveryCompany;
use App\Filament\Resources\DeliveryCompanies\Pages\ListDeliveryCompanies;
use App\Filament\Resources\DeliveryCompanies\Pages\ViewDeliveryCompany;
use App\Filament\Resources\DeliveryCompanies\Schemas\DeliveryCompanyForm;
use App\Filament\Resources\DeliveryCompanies\Schemas\DeliveryCompanyInfolist;
use App\Filament\Resources\DeliveryCompanies\Tables\DeliveryCompaniesTable;
use App\Models\DeliveryCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryCompanyResource extends Resource
{
    protected static ?string $model = DeliveryCompany::class;

    protected static ?string $modelLabel = 'Delivery company';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return DeliveryCompanyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DeliveryCompanyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryCompaniesTable::configure($table);
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
            'index' => ListDeliveryCompanies::route('/'),
            'create' => CreateDeliveryCompany::route('/create'),
            'view' => ViewDeliveryCompany::route('/{record}'),
            'edit' => EditDeliveryCompany::route('/{record}/edit'),
        ];
    }
}
