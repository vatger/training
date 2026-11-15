<?php

namespace App\Filament\Resources\LeadingMentors;

use App\Filament\Resources\LeadingMentors\Pages\CreateLeadingMentor;
use App\Filament\Resources\LeadingMentors\Pages\EditLeadingMentor;
use App\Filament\Resources\LeadingMentors\Pages\ListLeadingMentors;
use App\Filament\Resources\LeadingMentors\Schemas\LeadingMentorForm;
use App\Filament\Resources\LeadingMentors\Tables\LeadingMentorsTable;
use App\Models\LeadingMentor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeadingMentorResource extends Resource
{
    protected static ?string $model = LeadingMentor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Leading Mentors';

    public static function form(Schema $schema): Schema
    {
        return LeadingMentorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadingMentorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Users & Access';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeadingMentors::route('/'),
            'create' => CreateLeadingMentor::route('/create'),
            'edit' => EditLeadingMentor::route('/{record}/edit'),
        ];
    }
}
