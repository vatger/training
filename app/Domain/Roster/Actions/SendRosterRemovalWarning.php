<?php

namespace App\Domain\Roster\Actions;

use App\Integrations\Vatger\VatgerClientInterface;
use Illuminate\Support\Facades\Log;

class SendRosterRemovalWarning
{
    public function __construct(
        private readonly VatgerClientInterface $vatgerClient,
    ) {}

    public function execute(int $vatsimId): void
    {
        $message =
            'You have not controlled in the past 11 months. '.
            'To remain on the VATSIM Germany roster, please log in and control within the next 35 days. '.
            'Otherwise, your account will be removed from the roster. '.
            'If you believe this is a mistake, please contact the ATD.';

        $result = $this->vatgerClient->sendNotification(
            vatsimId: $vatsimId,
            title: 'Removal from VATSIM Germany Roster',
            message: $message,
            sourceName: 'VATGER ATD',
            linkUrl: 'https://vatsim-germany.org',
        );

        if (! $result['success']) {
            Log::error('Failed to send roster removal warning', ['vatsim_id' => $vatsimId]);
        }
    }
}
