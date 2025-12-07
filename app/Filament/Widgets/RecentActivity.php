<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\WaitingListEntry;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivity extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WaitingListEntry::query()
                    ->with(['user', 'course'])
                    ->latest('date_added')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.vatsim_id')
                    ->label('VATSIM ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('course.name')
                    ->label('Course')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\BadgeColumn::make('course.type')
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

                Tables\Columns\TextColumn::make('activity')
                    ->label('Activity')
                    ->suffix('h')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_added')
                    ->label('Joined Queue')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->heading('Recent Waiting List Activity')
            ->description('Latest 10 users who joined course waiting lists');
    }
}