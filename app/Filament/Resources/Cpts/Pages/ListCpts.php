<?php

namespace App\Filament\Resources\Cpts\Pages;

use App\Filament\Resources\Cpts\CptResource;
use Filament\Resources\Pages\ListRecords;

class ListCpts extends ListRecords
{
    protected static string $resource = CptResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}