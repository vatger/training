<?php

namespace App\Jobs;

use App\Domain\Gdpr\Actions\ApproveGdprRemoval;
use App\Domain\Gdpr\UserDataMap;
use App\Models\GdprRemovalRequest;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Removes users that have nothing stored besides their user model once nobody
 * approved the request by hand for GdprRemovalRequest::AUTO_REMOVE_AFTER_DAYS.
 * There is nothing to review for them, so no human decision is needed.
 */
class AutoRemoveGdprRequest implements ShouldQueue
{
    use Queueable;

    public function __construct(private int $requestId) {}

    public static function dispatchForDue(): void
    {
        GdprRemovalRequest::dueForAutoRemoval()->pluck('id')->each(fn (int $id) => static::dispatch($id));
    }

    public function handle(ApproveGdprRemoval $approve): void
    {
        $request = GdprRemovalRequest::dueForAutoRemoval()->find($this->requestId);
        $user = $request ? User::find($request->user_id) : null;

        // Without the user there is nothing to judge, so that case stays with a person.
        if (! $user || UserDataMap::hasSpecialData($user)) {
            return;
        }

        $approve->execute($request, null);

        Log::info('GDPR removal request approved automatically, user had no data', ['request_id' => $request->id]);
    }
}
