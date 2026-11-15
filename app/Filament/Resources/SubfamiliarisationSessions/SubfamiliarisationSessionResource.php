<?php

namespace App\Filament\Resources\SubfamiliarisationSessions;

use App\Filament\Clusters\FamiliarisationCluster;
use App\Filament\Resources\SubfamiliarisationSessions\Pages\CreateSubfamiliarisationSession;
use App\Filament\Resources\SubfamiliarisationSessions\Pages\EditSubfamiliarisationSession;
use App\Filament\Resources\SubfamiliarisationSessions\Pages\ListSubfamiliarisationSessions;
use App\Filament\Resources\SubfamiliarisationSessions\Schemas\SubfamiliarisationSessionForm;
use App\Filament\Resources\SubfamiliarisationSessions\Tables\SubfamiliarisationSessionsTable;
use App\Models\SubfamiliarisationSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubfamiliarisationSessionResource extends Resource
{
    protected static ?string $model = SubfamiliarisationSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $cluster = FamiliarisationCluster::class;

    public static function form(Schema $schema): Schema
    {
        return SubfamiliarisationSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubfamiliarisationSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubfamiliarisationSessions::route('/'),
            'create' => CreateSubfamiliarisationSession::route('/create'),
            'edit' => EditSubfamiliarisationSession::route('/{record}/edit'),
        ];
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getNavigationLabel(): string
    {
        return 'Subfamiliarisation Sessions';
    }
}
