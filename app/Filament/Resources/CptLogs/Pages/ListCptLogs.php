<?php

namespace App\Filament\Resources\CptLogs\Pages;

use App\Filament\Resources\CptLogs\CptLogResource;
use Filament\Resources\Pages\ListRecords;

class ListCptLogs extends ListRecords
{
    protected static string $resource = CptLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}