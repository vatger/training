<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class EndorsementsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static ?string $navigationLabel = 'Endorsements';

    public static function getNavigationGroup(): ?string
    {
        return 'Endorsements & Ratings';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }
}
