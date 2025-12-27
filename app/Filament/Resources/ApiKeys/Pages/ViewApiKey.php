<?php

namespace App\Filament\Resources\ApiKeys\Pages;

use App\Filament\Resources\ApiKeys\ApiKeyResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ViewApiKey extends Page
{
    protected static string $resource = ApiKeyResource::class;

    protected string $view = 'filament.resources.api-keys.pages.view-api-key';

    protected static ?string $title = 'API Key Created';

    public $apiKey;

    public function mount(): void
    {
        $this->apiKey = session('created_api_key');
        
        if (!$this->apiKey) {
            $this->redirect($this->getResource()::getUrl('index'));
        }
        
        session()->forget('created_api_key');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('done')
                ->label('Done')
                ->color('success')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}