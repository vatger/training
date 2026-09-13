<?php

namespace App\Domain\Activity\Reference;

use App\Integrations\Datahub\DatahubClientInterface;
use App\Integrations\VatglassesData\VatglassesDataClientInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Owns the raw on-disk mirror of the VATGLASSES + datahub reference data.
 *
 * The mirror lives under {disk}/{path} (config activity.mirror). A content hash
 * over every mirrored file is the "data version" that stamps every downstream
 * cache key, so stale caches are simply ignored after a data change.
 */
class ActivityDataMirror
{
    public const VERSION_CACHE_KEY = 'activity:data:version';

    public function __construct(
        private readonly VatglassesDataClientInterface $vatglasses,
        private readonly DatahubClientInterface $datahub,
    ) {}

    /**
     * Fetch from both sources and rewrite the mirror.
     *
     * @return array{version:string, changed:bool, files:int}
     */
    public function sync(): array
    {
        $previous = $this->version();

        /** @var array<string,string> $files */
        $files = [...$this->vatglasses->fetch(), ...$this->datahub->fetch()];
        ksort($files);

        $disk = $this->disk();
        $root = $this->path();

        // Clear stale files, then write the fresh set.
        foreach ($disk->allFiles($root) as $existing) {
            $disk->delete($existing);
        }

        foreach ($files as $relative => $contents) {
            $disk->put("{$root}/{$relative}", $contents);
        }

        $version = $this->computeVersion($files);
        Cache::forever(self::VERSION_CACHE_KEY, $version);

        return [
            'version' => $version,
            'changed' => $version !== $previous,
            'files' => count($files),
        ];
    }

    /** Current data version, or an empty-state sentinel if nothing is mirrored. */
    public function version(): string
    {
        $cached = Cache::get(self::VERSION_CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $files = $this->readAll();

        if ($files === []) {
            return 'empty';
        }

        $version = $this->computeVersion($files);
        Cache::forever(self::VERSION_CACHE_KEY, $version);

        return $version;
    }

    /**
     * @return array<string,string> mirror-relative path => raw JSON
     */
    public function readAll(): array
    {
        $disk = $this->disk();
        $root = $this->path();
        $result = [];

        foreach ($disk->allFiles($root) as $absolute) {
            $relative = ltrim(str_replace($root, '', $absolute), '/');
            $result[$relative] = (string) $disk->get($absolute);
        }

        ksort($result);

        return $result;
    }

    /**
     * @return array<string,mixed> decoded JSON for one mirrored file
     */
    public function readJson(string $relative): array
    {
        $disk = $this->disk();
        $absolute = $this->path()."/{$relative}";

        if (! $disk->exists($absolute)) {
            return [];
        }

        $decoded = json_decode((string) $disk->get($absolute), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string,string>  $files
     */
    private function computeVersion(array $files): string
    {
        ksort($files);

        $hash = hash_init('sha256');

        foreach ($files as $relative => $contents) {
            hash_update($hash, $relative."\0".hash('sha256', $contents)."\n");
        }

        return substr(hash_final($hash), 0, 16);
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('activity.mirror.disk', 'local'));
    }

    private function path(): string
    {
        return trim((string) config('activity.mirror.path', 'activity'), '/');
    }
}
