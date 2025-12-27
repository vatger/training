<?php

namespace App\Filament\Resources\ApiKeys\Pages;

use App\Filament\Resources\ApiKeys\ApiKeyResource;
use App\Models\ApiKey;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateApiKey extends CreateRecord
{
    protected static string $resource = ApiKeyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['key'] = ApiKey::generateKey();
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $plainKey = $this->record->plainKey;

        Notification::make()
            ->success()
            ->title('API Key Created')
            ->body("**Save this key now - it won't be shown again:**\n\n`{$plainKey}`")
            ->persistent()
            ->send();
    }
}