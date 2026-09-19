<?php

namespace App\Filament\Resources\Cpts\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Cpts\CptResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ViewCpt extends ViewRecord
{
    protected static string $resource = CptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),

            Action::make('delete_log')
                ->label('Delete a Log')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => $this->record->logs()->exists())
                ->schema([
                    Select::make('log_id')
                        ->label('Log to delete')
                        ->options(fn () => $this->record->logs()->get()->mapWithKeys(
                            fn ($log) => [$log->id => $log->file_name],
                        ))
                        ->required(),
                ])
                ->requiresConfirmation()
                ->modalHeading('Delete CPT Log')
                ->modalDescription('This permanently deletes the log file. This is admin-only — there is no other way to remove a log.')
                ->modalSubmitActionLabel('Yes, delete')
                ->action(function (array $data) {
                    $log = $this->record->logs()->find($data['log_id']);

                    if (! $log) {
                        return;
                    }

                    $log->deleteFile();

                    if ($this->record->logs()->count() === 1) {
                        $this->record->update(['log_uploaded' => false]);
                    }

                    $log->delete();

                    Notification::make()
                        ->title('Log deleted')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->record;

        return $schema
            ->components([
                Section::make('CPT Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('id')
                                    ->label('CPT ID')
                                    ->content(fn ($record) => $record->id),

                                Placeholder::make('date')
                                    ->label('CPT Date & Time')
                                    ->content(fn ($record) => $record->date->format('Y-m-d H:i')),

                                Placeholder::make('confirmed')
                                    ->label('Confirmed')
                                    ->content(fn ($record) => $record->confirmed ? '✓ Yes' : '✗ No'),
                            ]),
                    ])->columns(1),

                Section::make('Participants')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('trainee')
                                    ->label('Trainee')
                                    ->content(fn ($record) => $record->trainee
                                        ? new HtmlString(
                                            '<a href="'.UserResource::getUrl('edit', ['record' => $record->trainee]).'" class="text-primary-600 hover:underline font-medium">'
                                            .e($record->trainee->name).' ('.e($record->trainee->vatsim_id).')'
                                            .'</a>'
                                        )
                                        : '-'
                                    ),

                                Placeholder::make('examiner')
                                    ->label('Examiner')
                                    ->content(fn ($record) => $record->examiner
                                        ? new HtmlString(
                                            '<a href="'.UserResource::getUrl('edit', ['record' => $record->examiner]).'" class="text-primary-600 hover:underline font-medium">'
                                            .e($record->examiner->name).' ('.e($record->examiner->vatsim_id).')'
                                            .'</a>'
                                        )
                                        : 'Not assigned'
                                    ),

                                Placeholder::make('local')
                                    ->label('Local Contact')
                                    ->content(fn ($record) => $record->local
                                        ? new HtmlString(
                                            '<a href="'.UserResource::getUrl('edit', ['record' => $record->local]).'" class="text-primary-600 hover:underline font-medium">'
                                            .e($record->local->name).' ('.e($record->local->vatsim_id).')'
                                            .'</a>'
                                        )
                                        : 'Not assigned'
                                    ),
                            ]),
                    ])->columns(1),

                Section::make('Course Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('course')
                                    ->label('Course')
                                    ->content(fn ($record) => $record->course
                                        ? new HtmlString(
                                            '<a href="'.CourseResource::getUrl('edit', ['record' => $record->course]).'" class="text-primary-600 hover:underline font-medium">'
                                            .e($record->course->name)
                                            .'</a>'
                                        )
                                        : '-'
                                    ),

                                Placeholder::make('solo_station')
                                    ->label('Solo Station')
                                    ->content(fn ($record) => $record->course?->solo_station ?? '-'),

                                Placeholder::make('position')
                                    ->label('Position')
                                    ->content(fn ($record) => $record->course?->position ?? '-'),
                            ]),
                    ])->columns(1),

                Section::make('Result')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('log_uploaded')
                                    ->label('Log Uploaded')
                                    ->content(fn ($record) => $record->log_uploaded
                                        ? new HtmlString('<span class="text-success-600 font-medium">✓ Yes</span>')
                                        : new HtmlString('<span class="text-warning-600 font-medium">✗ No</span>')
                                    ),

                                Placeholder::make('passed')
                                    ->label('Result')
                                    ->content(fn ($record) => match ($record->passed) {
                                        true => new HtmlString('<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-700 ring-success-600/20">Passed</span>'),
                                        false => new HtmlString('<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-danger-50 text-danger-700 ring-danger-600/20">Failed</span>'),
                                        null => new HtmlString('<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-warning-50 text-warning-700 ring-warning-600/20">Pending</span>'),
                                    }),
                            ]),
                    ])->columns(1),

                Section::make('Logs')
                    ->schema([
                        RepeatableEntry::make('logs')
                            ->hiddenLabel()
                            ->state(fn ($record) => $record->logs()->with('uploadedBy')->latest()->get())
                            ->table([
                                TableColumn::make('File'),
                                TableColumn::make('Uploaded By'),
                                TableColumn::make('Uploaded At'),
                            ])
                            ->schema([
                                TextEntry::make('file_name')
                                    ->url(fn ($record) => $record->file_url)
                                    ->openUrlInNewTab(),
                                TextEntry::make('uploadedBy.name')->placeholder('—'),
                                TextEntry::make('created_at')->dateTime(),
                            ])
                            ->placeholder('No logs uploaded yet. Uploaded from the main app by the examiner or local contact.'),
                    ])
                    ->collapsible(),

                Section::make('Timestamps')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('created_at')
                                    ->label('Created')
                                    ->content(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),

                                Placeholder::make('updated_at')
                                    ->label('Updated')
                                    ->content(fn ($record) => $record->updated_at->format('Y-m-d H:i:s')),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
