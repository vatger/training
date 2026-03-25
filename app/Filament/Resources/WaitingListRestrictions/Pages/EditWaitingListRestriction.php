<?php

namespace App\Filament\Resources\WaitingListRestrictions\Pages;

use App\Filament\Resources\WaitingListRestrictions\WaitingListRestrictionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\EditRecord;

class EditWaitingListRestriction extends EditRecord
{
    protected static string $resource = WaitingListRestrictionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
