<?php

namespace App\Integrations\Vatger;

interface VatgerClientInterface
{
    public function postConfirmedCpts(array $cpts): array;

    public function sendNotification(
        int $vatsimId,
        string $title,
        string $message,
        string $sourceName,
        string $linkUrl,
        string $linkText = '',
    ): array;
}
