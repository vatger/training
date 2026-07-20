<?php

namespace App\Filament\Resources\ChiefOfTrainings\Pages;

use App\Filament\Resources\ChiefOfTrainings\ChiefOfTrainingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChiefOfTrainings extends ListRecords
{
    protected static string $resource = ChiefOfTrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
