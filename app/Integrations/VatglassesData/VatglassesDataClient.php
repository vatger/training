<?php

namespace App\Integrations\VatglassesData;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class VatglassesDataClient implements VatglassesDataClientInterface
{
    private string $rawBase;

    /** @var list<string> */
    private array $files;

    public function __construct()
    {
        $config = config('activity.sources.vatglasses');

        $this->rawBase = rtrim((string) $config['raw_base'], '/');
        $this->files = array_values((array) $config['files']);
    }

    public function fetch(): array
    {
        $result = [];

        foreach ($this->files as $file) {
            $file = ltrim($file, '/');
            $url = "{$this->rawBase}/{$file}";

            $response = Http::timeout(30)->retry(3, 2000, throw: false)->get($url);

            if (! $response->successful()) {
                throw new RuntimeException(
                    "Failed to fetch VATGLASSES data file {$file} ({$response->status()})",
                );
            }

            $body = $response->body();

            if (json_decode($body) === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("VATGLASSES data file {$file} is not valid JSON");
            }

            $result["vatglasses/{$file}"] = $body;
        }

        return $result;
    }
}
