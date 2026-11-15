<?php

namespace App\Domain\Gdpr\Actions;

use App\Models\GdprRemovalRequest;

class CompleteGdprRemoval
{
    /**
     * Closes the request. From now on the hourly poll from the homepage is answered
     * with success. Only the VATSIM id stays, as proof the erasure took place.
     */
    public function execute(GdprRemovalRequest $request): void
    {
        $request->update([
            'status' => GdprRemovalRequest::STATUS_COMPLETED,
            'completed_at' => now(),
            'user_name' => null,
            'search_terms' => null,
        ]);
    }
}
