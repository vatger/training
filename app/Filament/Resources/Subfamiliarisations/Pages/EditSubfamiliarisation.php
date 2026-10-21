<?php

namespace App\Filament\Resources\Subfamiliarisations\Pages;

use App\Filament\Resources\Subfamiliarisations\SubfamiliarisationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubfamiliarisation extends EditRecord
{
    protected static string $resource = SubfamiliarisationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
