<?php

namespace App\Filament\Resources\TrainingLogs\Pages;

use App\Filament\Resources\TrainingLogs\TrainingLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTrainingLog extends ViewRecord
{
    protected static string $resource = TrainingLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}