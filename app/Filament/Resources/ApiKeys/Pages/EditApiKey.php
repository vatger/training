<?php

namespace App\Filament\Resources\ApiKeys\Pages;

use App\Filament\Resources\ApiKeys\ApiKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApiKey extends EditRecord
{
    protected static string $resource = ApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}