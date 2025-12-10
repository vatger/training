<?php

namespace App\Filament\Resources\CptLogs\Pages;

use App\Filament\Resources\CptLogs\CptLogResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Cpts\CptResource;
use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Facades\Storage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;

class ViewCptLog extends ViewRecord
{
    protected static string $resource = CptLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_pdf')
                ->label('Open PDF')
                ->icon('heroicon-o-document-text')
                ->url(fn ($record) => route('cpt.log.view', $record->id))
                ->openUrlInNewTab()
                ->visible(fn ($record) => $this->fileExists($record)),
            
            DeleteAction::make()
                ->label('Delete Log')
                ->before(function ($record) {
                    if (Storage::disk('private')->exists($record->log_file)) {
                        Storage::disk('private')->delete($record->log_file);
                    } elseif (Storage::disk('public')->exists($record->log_file)) {
                        Storage::disk('public')->delete($record->log_file);
                    }
                    
                    $cpt = $record->cpt;
                    if ($cpt && $cpt->logs()->count() === 1) {
                        $cpt->update(['log_uploaded' => false]);
                    }
                })
                ->successRedirectUrl(route('filament.admin.resources.cpt-logs.index')),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->record;
        $record->load(['cpt.trainee', 'cpt.examiner', 'cpt.local', 'cpt.course', 'uploadedBy']);

        return $schema
            ->components([
                Section::make('Log Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('id')
                                    ->label('Log ID')
                                    ->content(fn($record) => $record->id),

                                Placeholder::make('file_name')
                                    ->label('File Name')
                                    ->content(fn($record) => $record->file_name),

                                Placeholder::make('created_at')
                                    ->label('Upload Date')
                                    ->content(fn($record) => $record->created_at->format('Y-m-d H:i:s')),
                            ]),

                        Grid::make(1)
                            ->schema([
                                Placeholder::make('file_status')
                                    ->label('File Status')
                                    ->content(fn($record) => $this->fileExists($record)
                                        ? new \Illuminate\Support\HtmlString('<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-700 ring-success-600/20">✓ File Available</span>')
                                        : new \Illuminate\Support\HtmlString('<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-danger-50 text-danger-700 ring-danger-600/20">✗ File Not Found</span>')
                                    ),
                            ]),
                    ])->columns(1),

                Section::make('Uploaded By')
                    ->schema([
                        Placeholder::make('uploaded_by')
                            ->label('User')
                            ->content(fn($record) => $record->uploadedBy 
                                ? new \Illuminate\Support\HtmlString(
                                    '<a href="' . UserResource::getUrl('edit', ['record' => $record->uploadedBy]) . '" class="text-primary-600 hover:underline font-medium">'
                                    . e($record->uploadedBy->name) . ' (' . e($record->uploadedBy->vatsim_id) . ')'
                                    . '</a>'
                                )
                                : 'Unknown'
                            ),
                    ])->columns(1),

                Section::make('CPT Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('cpt')
                                    ->label('CPT')
                                    ->content(fn($record) => $record->cpt 
                                        ? new \Illuminate\Support\HtmlString(
                                            '<a href="' . CptResource::getUrl('view', ['record' => $record->cpt]) . '" class="text-primary-600 hover:underline font-medium">'
                                            . 'CPT #' . e($record->cpt->id)
                                            . '</a>'
                                        )
                                        : 'Unknown'
                                    ),

                                Placeholder::make('trainee')
                                    ->label('Trainee')
                                    ->content(fn($record) => $record->cpt?->trainee 
                                        ? new \Illuminate\Support\HtmlString(
                                            '<a href="' . UserResource::getUrl('edit', ['record' => $record->cpt->trainee]) . '" class="text-primary-600 hover:underline font-medium">'
                                            . e($record->cpt->trainee->name) . ' (' . e($record->cpt->trainee->vatsim_id) . ')'
                                            . '</a>'
                                        )
                                        : 'Unknown'
                                    ),

                                Placeholder::make('cpt_date')
                                    ->label('CPT Date')
                                    ->content(fn($record) => $record->cpt?->date 
                                        ? $record->cpt->date->format('Y-m-d H:i')
                                        : '-'
                                    ),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Placeholder::make('examiner')
                                    ->label('Examiner')
                                    ->content(fn($record) => $record->cpt?->examiner 
                                        ? new \Illuminate\Support\HtmlString(
                                            '<a href="' . UserResource::getUrl('edit', ['record' => $record->cpt->examiner]) . '" class="text-primary-600 hover:underline font-medium">'
                                            . e($record->cpt->examiner->name) . ' (' . e($record->cpt->examiner->vatsim_id) . ')'
                                            . '</a>'
                                        )
                                        : 'Not assigned'
                                    ),

                                Placeholder::make('local')
                                    ->label('Local Contact')
                                    ->content(fn($record) => $record->cpt?->local 
                                        ? new \Illuminate\Support\HtmlString(
                                            '<a href="' . UserResource::getUrl('edit', ['record' => $record->cpt->local]) . '" class="text-primary-600 hover:underline font-medium">'
                                            . e($record->cpt->local->name) . ' (' . e($record->cpt->local->vatsim_id) . ')'
                                            . '</a>'
                                        )
                                        : 'Not assigned'
                                    ),

                                Placeholder::make('result')
                                    ->label('Result')
                                    ->content(fn($record) => match($record->cpt?->passed) {
                                        true => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-success-50 text-success-700 ring-success-600/20">Passed</span>'),
                                        false => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-danger-50 text-danger-700 ring-danger-600/20">Failed</span>'),
                                        null => new \Illuminate\Support\HtmlString('<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-warning-50 text-warning-700 ring-warning-600/20">Pending</span>'),
                                    }),
                            ]),
                    ])->columns(1),

                Section::make('Course Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('course')
                                    ->label('Course')
                                    ->content(fn($record) => $record->cpt?->course 
                                        ? new \Illuminate\Support\HtmlString(
                                            '<a href="' . CourseResource::getUrl('edit', ['record' => $record->cpt->course]) . '" class="text-primary-600 hover:underline font-medium">'
                                            . e($record->cpt->course->name)
                                            . '</a>'
                                        )
                                        : '-'
                                    ),

                                Placeholder::make('solo_station')
                                    ->label('Solo Station')
                                    ->content(fn($record) => $record->cpt?->course?->solo_station ?? '-'),

                                Placeholder::make('position')
                                    ->label('Position')
                                    ->content(fn($record) => $record->cpt?->course?->position ?? '-'),
                            ]),
                    ])->columns(1),

                Section::make('File Not Found')
                    ->schema([
                        Placeholder::make('error')
                            ->label('Error')
                            ->content('The log file could not be found in storage. The file may have been deleted or moved.')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn($record) => !$this->fileExists($record)),
            ]);
    }

    protected function fileExists($record): bool
    {
        if (Storage::disk('private')->exists($record->log_file)) {
            return true;
        }
        
        if (Storage::disk('public')->exists($record->log_file)) {
            return true;
        }
        
        return false;
    }
}