<?php

namespace App\Integrations\VatsimGermanyStats;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VatsimGermanyStatsClient implements VatsimGermanyStatsClientInterface
{
    private string $baseUrl;

    private int $timeout;

    private int $retries;

    private int $maxWindowDays;

    private int $requestDelayMs;

    private int $perPage;

    private int $maxPages;

    public function __construct()
    {
        $config = config('activity.sources.sessions');

        $this->baseUrl = rtrim((string) $config['base_url'], '/');
        $this->timeout = (int) $config['timeout'];
        $this->retries = (int) $config['retries'];
        $this->maxWindowDays = max(1, (int) $config['max_window_days']);
        $this->requestDelayMs = max(0, (int) $config['request_delay_ms']);
        $this->perPage = min(5000, max(1, (int) $config['per_page']));
        $this->maxPages = max(1, (int) $config['max_pages']);
    }

    public function getSessionsInRange(CarbonInterface $from, CarbonInterface $to, array $callsignPrefixes = []): array
    {
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);

        if ($from->greaterThanOrEqualTo($to)) {
            return [];
        }

        $query = [
            'start_date' => $from->toIso8601String(),
            'end_date' => $to->toIso8601String(),
            'per_page' => $this->perPage,
        ];

        if ($callsignPrefixes !== []) {
            $query['callsign_prefix'] = implode(',', $callsignPrefixes);
        }

        $url = "{$this->baseUrl}/atc/sessions";
        $byId = [];
        $cursor = null;
        $pages = 0;

        do {
            if ($cursor !== null) {
                $query['cursor'] = $cursor;
            }

            try {
                $response = Http::timeout($this->timeout)
                    ->retry($this->retries, 1000, throw: false)
                    ->acceptJson()
                    ->get($url, $query);
            } catch (\Throwable $e) {
                Log::error('vatsim-germany range request errored', ['error' => $e->getMessage(), 'url' => $url]);
                break;
            }

            if (! $response->successful()) {
                Log::warning('vatsim-germany range request failed', [
                    'status' => $response->status(),
                    'from' => $from->toIso8601String(),
                    'to' => $to->toIso8601String(),
                ]);
                break;
            }

            $payload = $response->json();
            $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];

            foreach ($rows as $row) {
                if (is_array($row) && isset($row['id'])) {
                    $byId[(int) $row['id']] = $row;
                }
            }

            $cursor = $payload['next_cursor'] ?? null;

            if ($this->requestDelayMs > 0 && $cursor !== null) {
                usleep($this->requestDelayMs * 1000);
            }
        } while ($cursor !== null && ++$pages < $this->maxPages);

        return array_values($byId);
    }

    public function getAtcSessions(int $cid, CarbonInterface $from, ?CarbonInterface $to = null): array
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();

        if ($from->greaterThan($to)) {
            return [];
        }

        $byId = [];

        foreach ($this->windows($from, $to) as [$windowStart, $windowEnd]) {
            foreach ($this->fetchWindow($cid, $windowStart, $windowEnd) as $session) {
                if (isset($session['id'])) {
                    $byId[(int) $session['id']] = $session;
                }
            }

            if ($this->requestDelayMs > 0) {
                usleep($this->requestDelayMs * 1000);
            }
        }

        return array_values($byId);
    }

    /**
     * @return iterable<array{0:Carbon,1:Carbon}>
     */
    private function windows(Carbon $from, Carbon $to): iterable
    {
        $cursor = $from->copy();

        while ($cursor->lessThanOrEqualTo($to)) {
            $windowEnd = $cursor->copy()->addDays($this->maxWindowDays)->subDay()->endOfDay();

            if ($windowEnd->greaterThan($to)) {
                $windowEnd = $to->copy();
            }

            yield [$cursor->copy(), $windowEnd];

            $cursor = $windowEnd->copy()->addDay()->startOfDay();
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function fetchWindow(int $cid, Carbon $from, Carbon $to): array
    {
        $url = "{$this->baseUrl}/atc/{$cid}/sessions/";

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retries, 1000, throw: false)
                ->acceptJson()
                ->get($url, [
                    'start_date' => $from->format('Y-m-d'),
                    'end_date' => $to->format('Y-m-d'),
                ]);

            if (! $response->successful()) {
                Log::warning('vatsim-germany stats request failed', [
                    'cid' => $cid,
                    'status' => $response->status(),
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ]);

                return [];
            }

            $data = $response->json();

            return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
        } catch (\Throwable $e) {
            Log::error('vatsim-germany stats request errored', [
                'cid' => $cid,
                'error' => $e->getMessage(),
                'url' => $url,
            ]);

            return [];
        }
    }
}
