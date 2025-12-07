<?php

namespace App\Filament\Resources\Cpts\Pages;

use App\Filament\Resources\Cpts\CptResource;
use App\Filament\Resources\Cpts\Schemas\CptForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditCpt extends EditRecord
{
    protected static string $resource = CptResource::class;

    public function form(Schema $schema): Schema
    {
        return CptForm::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}