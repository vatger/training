<?php

namespace App\Filament\Resources\Familiarisations\Pages;

use App\Filament\Resources\Familiarisations\FamiliarisationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFamiliarisation extends EditRecord
{
    protected static string $resource = FamiliarisationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}