<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class FamiliarisationCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeEuropeAfrica;

    protected static ?string $navigationLabel = 'Familiarisations';

    public static function getNavigationGroup(): ?string
    {
        return 'Endorsements & Ratings';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }
}
