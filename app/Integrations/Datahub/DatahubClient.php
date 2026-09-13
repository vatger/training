<?php

namespace App\Integrations\Datahub;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DatahubClient implements DatahubClientInterface
{
    private string $rawBase;

    /** @var list<string> */
    private array $firs;

    /** @var list<string> */
    private array $types;

    private ?string $stationsFile;

    public function __construct()
    {
        $config = config('activity.sources.datahub');

        $this->rawBase = rtrim((string) $config['raw_base'], '/');
        $this->firs = array_values(array_map('strtolower', (array) $config['firs']));
        $this->types = array_values(array_map('strtolower', (array) $config['types']));
        $this->stationsFile = $config['stations_file'] ? ltrim((string) $config['stations_file'], '/') : null;
    }

    public function fetch(): array
    {
        $result = [];

        foreach ($this->firs as $fir) {
            foreach ($this->types as $type) {
                $relative = "{$fir}/{$type}.json";
                $body = $this->get($relative, required: false);

                if ($body !== null) {
                    $result["datahub/{$relative}"] = $body;
                }
            }
        }

        if ($this->stationsFile !== null) {
            $body = $this->get($this->stationsFile, required: false);

            if ($body !== null) {
                $result['datahub/'.basename($this->stationsFile)] = $body;
            }
        }

        if ($result === []) {
            throw new RuntimeException('datahub fetch returned no files; check ACTIVITY_DATAHUB_* config');
        }

        return $result;
    }

    private function get(string $relative, bool $required): ?string
    {
        $url = "{$this->rawBase}/{$relative}";

        $response = Http::timeout(30)->retry(3, 2000, throw: false)->get($url);

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            if ($required) {
                throw new RuntimeException("Failed to fetch datahub file {$relative} ({$response->status()})");
            }

            Log::warning('datahub file fetch failed', ['file' => $relative, 'status' => $response->status()]);

            return null;
        }

        $body = $response->body();

        if (json_decode($body) === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("datahub file {$relative} is not valid JSON");
        }

        return $body;
    }
}
