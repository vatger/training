<?php

namespace App\Filament\Support;

use App\Models\User;
use Closure;

class UserSearch
{
    /**
     * Returns a getSearchResultsUsing callback for Select components that
     * searches by VATSIM ID (starts-with) or by first/last name (all words must match).
     */
    public static function callback(): Closure
    {
        return function (string $search): array {
            $search = trim($search);

            $query = User::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(50);

            if (filled($search)) {
                if (ctype_digit($search)) {
                    $query->where('vatsim_id', 'like', "{$search}%");
                } else {
                    $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($terms as $term) {
                        $query->where(function ($q) use ($term): void {
                            $q->where('first_name', 'like', "%{$term}%")
                                ->orWhere('last_name', 'like', "%{$term}%");
                        });
                    }
                }
            }

            return $query->get()->mapWithKeys(
                fn (User $user) => [$user->id => self::format($user)]
            )->toArray();
        };
    }

    /**
     * Returns a getOptionLabelFromRecordUsing callback so selected values
     * display consistently with search results.
     */
    public static function optionLabel(): Closure
    {
        return fn (User $record) => self::format($record);
    }

    private static function format(User $user): string
    {
        return "{$user->first_name} {$user->last_name} ({$user->vatsim_id})";
    }
}
