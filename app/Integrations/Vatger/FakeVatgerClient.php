<?php

namespace App\Integrations\Vatger;

use Illuminate\Support\Facades\Log;

class FakeVatgerClient implements VatgerClientInterface
{
    public function postConfirmedCpts(array $cpts): array
    {
        Log::info('[FakeVatger] postConfirmedCpts', ['cpt_count' => count($cpts)]);

        return ['success' => true];
    }

    public function sendNotification(
        int $vatsimId,
        string $title,
        string $message,
        string $sourceName,
        string $linkUrl,
        string $linkText = '',
    ): array {
        Log::info('[FakeVatger] sendNotification', ['vatsim_id' => $vatsimId, 'title' => $title]);

        return ['success' => true];
    }
}