<?php

namespace App\Integrations\VatglassesData;

use RuntimeException;

/**
 * Reads VATGLASSES fixtures from tests/Fixtures/Activity/vatglasses.
 * Override the source directory or payload with {@see self::from()} / {@see self::fake()}.
 */
class FakeVatglassesDataClient implements VatglassesDataClientInterface
{
    private ?string $directory = null;

    /** @var array<string,string>|null */
    private ?array $payload = null;

    public function from(string $directory): self
    {
        $this->directory = rtrim($directory, '/');

        return $this;
    }

    /**
     * @param  array<string,string>  $payload  mirror-relative path => raw JSON
     */
    public function fake(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function fetch(): array
    {
        if ($this->payload !== null) {
            return $this->payload;
        }

        $directory = $this->directory ?? base_path('tests/Fixtures/Activity/vatglasses');

        if (! is_dir($directory)) {
            throw new RuntimeException("VATGLASSES fixture directory not found: {$directory}");
        }

        $result = [];

        foreach (glob("{$directory}/*.json") ?: [] as $path) {
            $result['vatglasses/'.basename($path)] = (string) file_get_contents($path);
        }

        return $result;
    }
}
