<?php

namespace App\Filament\Resources\ChiefOfTrainings\Pages;

use App\Filament\Resources\ChiefOfTrainings\ChiefOfTrainingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChiefOfTraining extends EditRecord
{
    protected static string $resource = ChiefOfTrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
