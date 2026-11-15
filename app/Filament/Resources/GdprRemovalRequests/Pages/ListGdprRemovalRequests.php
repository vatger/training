<?php

namespace App\Filament\Resources\GdprRemovalRequests\Pages;

use App\Filament\Resources\GdprRemovalRequests\GdprRemovalRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListGdprRemovalRequests extends ListRecords
{
    protected static string $resource = GdprRemovalRequestResource::class;
}
