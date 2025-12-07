<?php

namespace App\Filament\Resources\Examiners\Pages;

use App\Filament\Resources\Examiners\ExaminerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExaminer extends EditRecord
{
    protected static string $resource = ExaminerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}