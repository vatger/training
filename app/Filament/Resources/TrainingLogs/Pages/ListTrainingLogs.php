<?php

namespace App\Filament\Resources\TrainingLogs\Pages;

use App\Filament\Resources\TrainingLogs\TrainingLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrainingLogs extends ListRecords
{
    protected static string $resource = TrainingLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}