<?php

namespace App\Filament\Resources\GdprRemovalRequests\RelationManagers;

use App\Domain\Gdpr\Actions\ReviewGdprItem;
use App\Domain\Gdpr\UserDataMap;
use App\Models\GdprReviewItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Records of other users that stay but may still reveal who was removed.
 */
class ReviewItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviewItems';

    protected static ?string $title = 'Records to review';

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ! $ownerRecord->isPending();
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('The user was removed from these records. Read the text for their name, VATSIM ID, email or anything else that identifies them, remove it, and mark the record as reviewed. The request closes once everything is reviewed.')
            ->defaultSort('reviewed_at')
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Record')
                    ->searchable(),

                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === GdprReviewItem::REASON_AUTHOR ? 'Written by the user' : 'Text mentions the user')
                    ->color(fn (string $state) => $state === GdprReviewItem::REASON_AUTHOR ? 'info' : 'warning'),

                Tables\Columns\IconColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->boolean(),

                Tables\Columns\TextColumn::make('reviewedBy.name')
                    ->label('Reviewed by')
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('review')
                    ->label(fn (GdprReviewItem $record) => $record->isReviewed() ? 'Review again' : 'Review')
                    ->icon('heroicon-o-magnifying-glass')
                    ->modalHeading(fn (GdprReviewItem $record) => $record->label)
                    ->modalDescription('This record belongs to another user and is kept. The removed user is already replaced by "GDPR Removed". Remove anything in the text that still identifies them; fields that contain one of their identifiers are flagged.')
                    ->modalSubmitActionLabel('Save and mark as reviewed')
                    ->modalWidth('4xl')
                    ->visible(fn (GdprReviewItem $record) => ! $record->request->isCompleted())
                    ->fillForm(fn (GdprReviewItem $record) => $this->currentValues($record))
                    ->schema(fn (GdprReviewItem $record) => $this->fieldsFor($record))
                    ->action(function (array $data, GdprReviewItem $record) {
                        app(ReviewGdprItem::class)->execute($record, auth()->user(), $data);

                        Notification::make()->title('Marked as reviewed')->success()->send();
                    }),
            ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function currentValues(GdprReviewItem $item): array
    {
        $fields = UserDataMap::reviewTables()[$item->table_name]['fields'];

        return (array) DB::table($item->table_name)->where('id', $item->record_id)->first($fields);
    }

    /**
     * Only the text fields that actually contain something are shown.
     *
     * @return array<int, Textarea>
     */
    private function fieldsFor(GdprReviewItem $item): array
    {
        $terms = $item->request->search_terms ?? [];

        return collect($this->currentValues($item))
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value, string $field) => Textarea::make($field)
                ->label(str($field)->replace('_', ' ')->title()->toString())
                ->rows(3)
                ->autosize()
                ->helperText(collect($terms)->contains(fn ($term) => stripos((string) $value, $term) !== false)
                    ? 'Contains a search term - check this text'
                    : null))
            ->values()
            ->all();
    }
}
