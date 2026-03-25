<?php

namespace App\Filament\Resources\WaitingListRestrictions\Pages;

use App\Filament\Resources\WaitingListRestrictions\WaitingListRestrictionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWaitingListRestrictions extends ListRecords
{
    protected static string $resource = WaitingListRestrictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
