<?php

namespace App\Filament\Resources\EndorsementActivities\Pages;

use App\Filament\Resources\EndorsementActivities\EndorsementActivityResource;
use Filament\Resources\Pages\ListRecords;

class ListEndorsementActivities extends ListRecords
{
    protected static string $resource = EndorsementActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}