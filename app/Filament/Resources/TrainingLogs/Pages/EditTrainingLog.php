<?php

namespace App\Filament\Resources\TrainingLogs\Pages;

use App\Filament\Resources\TrainingLogs\TrainingLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrainingLog extends EditRecord
{
    protected static string $resource = TrainingLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}