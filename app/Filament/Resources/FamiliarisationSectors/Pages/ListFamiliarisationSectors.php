<?php

namespace App\Filament\Resources\FamiliarisationSectors\Pages;

use App\Filament\Resources\FamiliarisationSectors\FamiliarisationSectorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFamiliarisationSectors extends ListRecords
{
    protected static string $resource = FamiliarisationSectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}