<?php

namespace App\Integrations\Datahub;

use RuntimeException;

/**
 * Reads datahub fixtures from tests/Fixtures/Activity/datahub (recursively).
 */
class FakeDatahubClient implements DatahubClientInterface
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

        $directory = $this->directory ?? base_path('tests/Fixtures/Activity/datahub');

        if (! is_dir($directory)) {
            throw new RuntimeException("datahub fixture directory not found: {$directory}");
        }

        $result = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }

            $relative = ltrim(str_replace($directory, '', $file->getPathname()), '/');
            $result["datahub/{$relative}"] = (string) file_get_contents($file->getPathname());
        }

        return $result;
    }
}
