<?php

namespace App\Filament\Resources\ApiKeys;

use App\Filament\Resources\ApiKeys\Pages\CreateApiKey;
use App\Filament\Resources\ApiKeys\Pages\EditApiKey;
use App\Filament\Resources\ApiKeys\Pages\ListApiKeys;
use App\Filament\Resources\ApiKeys\Schemas\ApiKeyForm;
use App\Filament\Resources\Apikeys\Tables\ApiKeysTable;
use App\Models\ApiKey;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ApiKeyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiKeysTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiKeys::route('/'),
            'create' => CreateApiKey::route('/create'),
            'edit' => EditApiKey::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System & Administration';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }
}