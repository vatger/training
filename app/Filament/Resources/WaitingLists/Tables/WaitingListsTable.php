<?php

namespace App\Filament\Resources\WaitingLists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WaitingListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Trainee')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('user.vatsim_id')
                    ->label('VATSIM ID')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('course.name')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                BadgeColumn::make('course.type')
                    ->label('Type')
                    ->colors([
                        'success' => 'RTG',
                        'warning' => 'EDMT',
                        'info' => 'GST',
                        'purple' => 'FAM',
                        'gray' => 'RST',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'RTG' => 'Rating',
                        'EDMT' => 'Endorsement',
                        'GST' => 'Visitor',
                        'FAM' => 'Familiarisation',
                        'RST' => 'Roster Reentry',
                        default => $state,
                    }),

                TextColumn::make('activity')
                    ->label('Activity')
                    ->suffix('h')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->color(fn ($state) => $state >= 10 ? 'success' : ($state >= 8 ? 'warning' : 'danger')),

                BadgeColumn::make('position_in_queue')
                    ->label('Position')
                    ->color('info'),

                TextColumn::make('date_added')
                    ->label('Added')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                TextColumn::make('waiting_time')
                    ->label('Waiting')
                    ->badge()
                    ->color('warning'),

                IconColumn::make('is_interested')
                    ->label('Interest')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (bool $state): string => $state ? 'Confirmed' : 'Pending confirmation')
                    ->sortable(),

                TextColumn::make('remarks')
                    ->label('Remarks')
                    ->limit(30)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('course')
                    ->relationship('course', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('course_type')
                    ->label('Course Type')
                    ->options([
                        'RTG' => 'Rating',
                        'EDMT' => 'Endorsement',
                        'GST' => 'Visitor',
                        'FAM' => 'Familiarisation',
                        'RST' => 'Roster Reentry',
                    ])
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('course', function ($q) use ($data) {
                                $q->where('type', $data['value']);
                            });
                        }
                    }),

                Filter::make('low_activity')
                    ->label('Low Activity (<10h)')
                    ->query(fn ($query) => $query->where('activity', '<', 10)),

                Filter::make('high_activity')
                    ->label('High Activity (≥10h)')
                    ->query(fn ($query) => $query->where('activity', '>=', 10)),

                Filter::make('multiple_entries')
                    ->label('Multiple Rating, EDMT or FAM Entries')
                    ->toggle()
                    ->query(function (Builder $query) {
                        $duplicateUserIdsForType = function (string $type) {
                            return function ($sub) use ($type) {
                                $sub->select('user_id')
                                    ->from('waiting_list_entries')
                                    ->join('courses', 'courses.id', '=', 'waiting_list_entries.course_id')
                                    ->where('courses.type', $type)
                                    ->groupBy('user_id')
                                    ->havingRaw('count(*) > 1');
                            };
                        };

                        return $query
                            ->where(function (Builder $query) use ($duplicateUserIdsForType) {
                                foreach (['RTG', 'EDMT', 'FAM'] as $type) {
                                    $query->orWhere(function (Builder $query) use ($duplicateUserIdsForType, $type) {
                                        $query
                                            ->whereHas('course', fn ($q) => $q->where('type', $type))
                                            ->whereIn('user_id', $duplicateUserIdsForType($type));
                                    });
                                }
                            })
                            ->orderBy('user_id');
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date_added', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([25, 50, 100, 200]);
    }
}
