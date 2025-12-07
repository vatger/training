<?php

namespace App\Filament\Resources\FamiliarisationSectors\Pages;

use App\Filament\Resources\FamiliarisationSectors\FamiliarisationSectorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFamiliarisationSector extends EditRecord
{
    protected static string $resource = FamiliarisationSectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}