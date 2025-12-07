<?php

namespace App\Filament\Resources\Tier2Endorsements\Pages;

use App\Filament\Resources\Tier2Endorsements\Tier2EndorsementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTier2Endorsement extends EditRecord
{
    protected static string $resource = Tier2EndorsementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}