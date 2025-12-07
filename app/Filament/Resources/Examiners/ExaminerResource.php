<?php

namespace App\Filament\Resources\Examiners;

use App\Filament\Resources\Examiners\Pages\EditExaminer;
use App\Filament\Resources\Examiners\Pages\ListExaminers;
use App\Filament\Resources\Examiners\Pages\CreateExaminer;
use App\Filament\Resources\Examiners\Schemas\ExaminerForm;
use App\Filament\Resources\Examiners\Tables\ExaminersTable;
use App\Models\Examiner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExaminerResource extends Resource
{
    protected static ?string $model = Examiner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'callsign';

    public static function form(Schema $schema): Schema
    {
        return ExaminerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExaminersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExaminers::route('/'),
            'create' => CreateExaminer::route('/create'),
            'edit' => EditExaminer::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Endorsements & Ratings';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getNavigationLabel(): string
    {
        return 'Examiners';
    }
}