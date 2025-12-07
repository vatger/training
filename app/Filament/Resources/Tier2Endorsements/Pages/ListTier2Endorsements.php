<?php

namespace App\Filament\Resources\Tier2Endorsements\Pages;

use App\Filament\Resources\Tier2Endorsements\Tier2EndorsementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTier2Endorsements extends ListRecords
{
    protected static string $resource = Tier2EndorsementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}