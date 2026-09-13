<?php

namespace App\Integrations\VatglassesData;

interface VatglassesDataClientInterface
{
    /**
     * Fetch the configured VATGLASSES dataset files.
     *
     * @return array<string,string> map of mirror-relative path
     *                              (e.g. "vatglasses/ed.json") => raw JSON
     */
    public function fetch(): array;
}
