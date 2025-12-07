<?php

namespace App\Filament\Resources\Examiners\Pages;

use App\Filament\Resources\Examiners\ExaminerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExaminers extends ListRecords
{
    protected static string $resource = ExaminerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}