<?php

namespace App\Filament\Resources\Subfamiliarisations\Pages;

use App\Filament\Resources\Subfamiliarisations\SubfamiliarisationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubfamiliarisations extends ListRecords
{
    protected static string $resource = SubfamiliarisationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
