<?php

namespace App\Jobs;

use App\Integrations\Vatger\VatgerClientInterface;
use App\Services\TeamOverview\TeamOverviewRenderer;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Rewrites the ATC-TD team overview forum post from the current admin panel data.
 *
 * Intentionally not queued: the scheduler runs it inline, so no queue worker is required.
 */
class UpdateTeamOverviewPost
{
    use Dispatchable;

    public function handle(TeamOverviewRenderer $renderer, VatgerClientInterface $vatger): void
    {
        $result = $vatger->postAtdTeamOverview($renderer->render());

        if (! $result['success']) {
            Log::error('Failed to update ATC-TD team overview post', ['message' => $result['message'] ?? null]);
        }
    }
}
