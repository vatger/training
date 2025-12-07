<?php

namespace App\Filament\Resources\WaitingLists;

use App\Filament\Resources\WaitingLists\Pages\EditWaitingList;
use App\Filament\Resources\WaitingLists\Pages\ListWaitingLists;
use App\Filament\Resources\WaitingLists\Pages\CreateWaitingList;
use App\Filament\Resources\WaitingLists\Schemas\WaitingListForm;
use App\Filament\Resources\WaitingLists\Tables\WaitingListsTable;
use App\Models\WaitingListEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WaitingListResource extends Resource
{
    protected static ?string $model = WaitingListEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return WaitingListForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WaitingListsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWaitingLists::route('/'),
            'create' => CreateWaitingList::route('/create'),
            'edit' => EditWaitingList::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Training';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getNavigationLabel(): string
    {
        return 'Waiting Lists';
    }
}