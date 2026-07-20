<?php

namespace App\Filament\Resources\LeadingMentors\Pages;

use App\Filament\Resources\LeadingMentors\LeadingMentorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeadingMentor extends EditRecord
{
    protected static string $resource = LeadingMentorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
