<?php

namespace App\Filament\Resources\LeadingMentors\Pages;

use App\Filament\Resources\LeadingMentors\LeadingMentorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeadingMentors extends ListRecords
{
    protected static string $resource = LeadingMentorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
