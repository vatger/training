<?php

namespace App\Filament\Resources\Familiarisations\Pages;

use App\Filament\Resources\Familiarisations\FamiliarisationResource;
use Filament\Resources\Pages\ListRecords;

class ListFamiliarisations extends ListRecords
{
    protected static string $resource = FamiliarisationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}