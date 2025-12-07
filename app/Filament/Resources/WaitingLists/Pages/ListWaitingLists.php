<?php

namespace App\Filament\Resources\WaitingLists\Pages;

use App\Filament\Resources\WaitingLists\WaitingListResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWaitingLists extends ListRecords
{
    protected static string $resource = WaitingListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}