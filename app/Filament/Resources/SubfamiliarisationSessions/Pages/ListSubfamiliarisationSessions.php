<?php

namespace App\Filament\Resources\SubfamiliarisationSessions\Pages;

use App\Filament\Resources\SubfamiliarisationSessions\SubfamiliarisationSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubfamiliarisationSessions extends ListRecords
{
    protected static string $resource = SubfamiliarisationSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
