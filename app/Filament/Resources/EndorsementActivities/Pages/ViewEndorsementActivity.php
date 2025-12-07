<?php

namespace App\Filament\Resources\EndorsementActivities\Pages;

use App\Filament\Resources\EndorsementActivities\EndorsementActivityResource;
use App\Filament\Resources\EndorsementActivities\Schemas\EndorsementActivityInfolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewEndorsementActivity extends ViewRecord
{
    protected static string $resource = EndorsementActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        return EndorsementActivityInfolist::configure($schema);
    }
}