<?php

namespace App\Domain\Gdpr\Actions;

use App\Domain\Gdpr\UserDataMap;
use App\Models\GdprReviewItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewGdprItem
{
    public function __construct(private CompleteGdprRemoval $complete) {}

    /**
     * Saves the edited free text (if any) and marks the item as reviewed. When it was
     * the last open item of an approved request, the request is closed.
     *
     * @param  array<string, string|null>  $fields  only columns listed in UserDataMap::reviewTables() are written
     */
    public function execute(GdprReviewItem $item, User $reviewer, array $fields = []): void
    {
        $allowed = UserDataMap::reviewTables()[$item->table_name]['fields'] ?? [];
        $fields = array_intersect_key($fields, array_flip($allowed));

        DB::transaction(function () use ($item, $reviewer, $fields) {
            if ($fields !== []) {
                DB::table($item->table_name)
                    ->where('id', $item->record_id)
                    ->update($fields + ['updated_at' => now()]);
            }

            $item->update(['reviewed_at' => now(), 'reviewed_by_id' => $reviewer->id]);
        });

        $request = $item->request;

        if ($request->isApproved() && ! $request->reviewItems()->whereNull('reviewed_at')->exists()) {
            $this->complete->execute($request);
        }
    }
}
