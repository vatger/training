<?php

namespace App\Filament\Resources\GdprRemovalRequests\Pages;

use App\Domain\Gdpr\Actions\ApproveGdprRemoval;
use App\Domain\Gdpr\UserDataMap;
use App\Filament\Resources\GdprRemovalRequests\GdprRemovalRequestResource;
use App\Models\GdprRemovalRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class ViewGdprRemovalRequest extends ViewRecord
{
    protected static string $resource = GdprRemovalRequestResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function getTitle(): string
    {
        return "GDPR request · VATSIM ID {$this->record->vatsim_id}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve and delete user')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => $this->record->isPending())
                ->requiresConfirmation()
                ->modalHeading('Delete this user and all of their data?')
                ->modalDescription('This permanently deletes the user and everything that belongs to them. Records of other users that they wrote are kept and show "GDPR Removed"; you review those in the next step. The homepage keeps getting 202 until that review is done. This cannot be undone.')
                ->modalSubmitActionLabel('Yes, delete everything')
                ->action(function () {
                    try {
                        app(ApproveGdprRemoval::class)->execute($this->record, auth()->user(), request()->ip());
                    } catch (\DomainException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        return;
                    }

                    $this->record->refresh();

                    Notification::make()
                        ->title($this->record->isCompleted() ? 'User deleted, request completed' : 'User deleted, remaining records need review')
                        ->success()
                        ->send();

                    // Reload so the steps, the relation manager and the header action reflect the new status.
                    $this->redirect(static::getUrl(['record' => $this->record]), navigate: true);
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(fn (GdprRemovalRequest $record) => match ($record->status) {
                GdprRemovalRequest::STATUS_PENDING => 'Step 1 · Approve the deletion of this user',
                GdprRemovalRequest::STATUS_APPROVED => 'Step 2 · Review the kept records',
                GdprRemovalRequest::STATUS_CANCELLED => 'Cancelled',
                default => 'Completed · all steps done',
            })
                ->columnSpanFull()
                ->schema([
                    View::make('filament.resources.gdpr-removal-requests.overview')
                        ->columnSpanFull()
                        ->viewData(function (GdprRemovalRequest $record): array {
                            $user = $record->isPending() ? User::find($record->user_id) : null;

                            return [
                                'request' => $record,
                                // Users without any special data skip the review and are removed automatically.
                                'hasSpecialData' => $record->isPending() ? ($user ? UserDataMap::hasSpecialData($user) : true) : $record->reviewItems()->exists(),
                                'data' => $user ? UserDataMap::collect($user) : null,
                                'terms' => $record->search_terms ?? [],
                                'mentions' => $user ? UserDataMap::mentions($record->search_terms ?? [], $user) : [],
                            ];
                        }),
                ]),
        ]);
    }
}
