<?php

namespace App\Filament\Resources\WaitingLists\Pages;

use App\Filament\Resources\WaitingLists\WaitingListResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWaitingList extends CreateRecord
{
    protected static string $resource = WaitingListResource::class;
}
