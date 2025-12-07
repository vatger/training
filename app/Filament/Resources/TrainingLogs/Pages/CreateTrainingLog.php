<?php

namespace App\Filament\Resources\TrainingLogs\Pages;

use App\Filament\Resources\TrainingLogs\TrainingLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrainingLog extends CreateRecord
{
    protected static string $resource = TrainingLogResource::class;
}