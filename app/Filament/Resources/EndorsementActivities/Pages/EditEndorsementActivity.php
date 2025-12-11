<?php

namespace App\Filament\Resources\EndorsementActivities\Pages;

use App\Filament\Resources\EndorsementActivities\EndorsementActivityResource;
use App\Filament\Resources\EndorsementActivities\Schemas\EndorsementActivityForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditEndorsementActivity extends EditRecord
{
    protected static string $resource = EndorsementActivityResource::class;

    public function form(Schema $schema): Schema
    {
        return EndorsementActivityForm::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('stop_removal')
                ->label('Stop Removal')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Stop Endorsement Removal')
                ->modalDescription('This will cancel the removal process for this endorsement.')
                ->action(function () {
                    $this->record->update([
                        'removal_date' => null,
                        'removal_notified' => false,
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Removal process stopped successfully.')
                        ->send();
                })
                ->visible(fn () => $this->record->removal_date !== null),
            
            DeleteAction::make(),
        ];
    }
}