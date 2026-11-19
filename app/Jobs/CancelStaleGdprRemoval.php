<?php

namespace App\Jobs;

use App\Models\GdprRemovalRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CancelStaleGdprRemoval implements ShouldQueue
{
    use Queueable;

    public function __construct(private int $requestId) {}

    public static function dispatchForStale(): void
    {
        GdprRemovalRequest::stale()->pluck('id')->each(fn (int $id) => static::dispatch($id));
    }

    public function handle(): void
    {
        $cancelled = GdprRemovalRequest::stale()
            ->whereKey($this->requestId)
            ->update([
                'status' => GdprRemovalRequest::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'user_name' => null,
                'search_terms' => null,
            ]);

        if ($cancelled) {
            Log::info('GDPR removal request cancelled, homepage stopped polling', ['request_id' => $this->requestId]);
        }
    }
}
