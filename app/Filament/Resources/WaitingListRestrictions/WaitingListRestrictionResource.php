<?php

namespace App\Filament\Resources\WaitingListRestrictions;

use App\Filament\Resources\WaitingListRestrictions\Pages\CreateWaitingListRestriction;
use App\Filament\Resources\WaitingListRestrictions\Pages\EditWaitingListRestriction;
use App\Filament\Resources\WaitingListRestrictions\Pages\ListWaitingListRestrictions;
use App\Filament\Resources\WaitingListRestrictions\Schemas\WaitingListRestrictionForm;
use App\Filament\Resources\WaitingListRestrictions\Tables\WaitingListRestrictionsTable;
use App\Models\WaitingListRestriction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WaitingListRestrictionResource extends Resource
{
    protected static ?string $model = WaitingListRestriction::class;

    protected static ?string $navigationLabel = 'List Bans';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::NoSymbol;

    protected static ?string $recordTitleAttribute = 'WaitingListRestriction';

    public static function form(Schema $schema): Schema
    {
        return WaitingListRestrictionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WaitingListRestrictionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Training';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWaitingListRestrictions::route('/'),
            'create' => CreateWaitingListRestriction::route('/create'),
            'edit' => EditWaitingListRestriction::route('/{record}/edit'),
        ];
    }
}
