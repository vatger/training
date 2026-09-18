<?php

namespace App\Filament\Resources\EndorsementActivities\Pages;

use App\Filament\Resources\EndorsementActivities\EndorsementActivityResource;
use App\Filament\Resources\EndorsementActivities\Schemas\EndorsementActivityInfolist;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewEndorsementActivity extends ViewRecord
{
    protected static string $resource = EndorsementActivityResource::class;

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

                    Notification::make()
                        ->success()
                        ->title('Removal process stopped successfully.')
                        ->send();
                })
                ->visible(fn () => $this->record->removal_date !== null),

            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return EndorsementActivityInfolist::configure($schema);
    }
}
