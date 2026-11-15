<?php

namespace App\Domain\Gdpr\Actions;

use App\Domain\Gdpr\Events\UserDeleted;
use App\Domain\Gdpr\UserDataMap;
use App\Integrations\VatEud\VatEudService;
use App\Models\ActivityLog;
use App\Models\GdprRemovalRequest;
use App\Models\GdprReviewItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApproveGdprRemoval
{
    private const REDACTED = 'GDPR Removed';

    public function __construct(
        private VatEudService $vatEudService,
        private CompleteGdprRemoval $complete,
    ) {}

    public function execute(GdprRemovalRequest $request, User $approver, ?string $ip = null): void
    {
        if (! $request->isPending()) {
            throw new \DomainException('Only pending GDPR requests can be approved.');
        }

        $user = User::find($request->user_id);

        if ($user && $user->is($approver)) {
            throw new \DomainException('You cannot approve your own GDPR removal request.');
        }

        $terms = $request->search_terms ?? [];

        // The external systems come first and are irreversible, so the audit trail is
        // written before anything that could still fail and roll back.
        event(new UserDeleted($request, $approver, $ip));

        $this->removeFromVatEud($request->vatsim_id, $user?->isVisitor() ?? false);

        $cptLogFiles = [];

        DB::transaction(function () use ($request, $approver, $user, $terms, &$cptLogFiles) {
            $reviewItems = [];

            if ($user) {
                $reviewItems = $this->detachAuthoredRecords($user, User::gdprPlaceholder());
                $cptLogFiles = $this->deleteOwnedRecords($user);

                $user->delete();
            }

            $this->redactActivityLogs($terms);

            foreach ($this->findMentions($terms) as $key => $item) {
                $reviewItems[$key] ??= $item;
            }

            foreach ($reviewItems as $item) {
                $request->reviewItems()->create($item);
            }

            $request->update([
                'status' => GdprRemovalRequest::STATUS_APPROVED,
                'approved_by_id' => $approver->id,
                'approved_at' => now(),
            ]);
        });

        foreach ($cptLogFiles as $path) {
            UserDataMap::deleteCptLogFile($path);
        }

        if (! $request->reviewItems()->exists()) {
            $this->complete->execute($request);
        }
    }

    /**
     * Re-points everything the user wrote on other people's records at the placeholder.
     * Returns the review items for the ones that carry free text.
     *
     * @return array<string, array<string, mixed>>
     */
    private function detachAuthoredRecords(User $user, User $placeholder): array
    {
        $reviewTables = UserDataMap::reviewTables();
        $items = [];

        foreach (UserDataMap::authored() as $source) {
            $table = $source['table'];
            $column = $source['column'];

            // Has to be read before the update, afterwards nothing tells these rows apart.
            if (isset($reviewTables[$table]) && in_array($column, $reviewTables[$table]['authors'], true)) {
                foreach (DB::table($table)->where($column, $user->id)->get() as $row) {
                    $items["{$table}:{$row->id}"] = $this->reviewItem($table, $row, GdprReviewItem::REASON_AUTHOR);
                }
            }

            DB::table($table)->where($column, $user->id)->update([$column => $placeholder->id]);
        }

        return $items;
    }

    /**
     * @return array<int, string> stored CPT log files, to be removed once the transaction committed
     */
    private function deleteOwnedRecords(User $user): array
    {
        $cptLogFiles = [];

        foreach (UserDataMap::owned() as $key => $source) {
            $query = $source['query']($user);

            if ($key === 'cpt_logs') {
                $cptLogFiles = (clone $query)->pluck('log_file')->all();
            }

            // Log entries about the records that are about to disappear contain their content.
            if (isset($source['model'])) {
                ActivityLog::where('model_type', $source['model'])
                    ->whereIn('model_id', (clone $query)->pluck('id')->all())
                    ->delete();
            }

            $query->delete();
        }

        return $cptLogFiles;
    }

    /**
     * Log entries that stay (they concern other people's records) may still spell out the
     * name or VATSIM id in their text. Those are replaced in place.
     *
     * @param  array<int, string>  $terms
     */
    private function redactActivityLogs(array $terms): void
    {
        if ($terms === []) {
            return;
        }

        ActivityLog::query()
            ->where('action', '!=', 'gdpr.deletion') // the audit entry of this very request has to keep the id
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('properties', 'like', "%{$term}%");
                }
            })
            ->each(function (ActivityLog $log) use ($terms) {
                $log->description = $log->description === null ? null : str_ireplace($terms, self::REDACTED, $log->description);
                $log->properties = $this->redactValue($log->properties, $terms);
                $log->saveQuietly();
            });
    }

    private function redactValue(mixed $value, array $terms): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->redactValue($item, $terms), $value);
        }

        if (is_string($value)) {
            return str_ireplace($terms, self::REDACTED, $value);
        }

        if (is_int($value) && in_array((string) $value, $terms, true)) {
            return self::REDACTED;
        }

        return $value;
    }

    /**
     * Rows of other people whose free text still contains one of the identifiers.
     *
     * @param  array<int, string>  $terms
     * @return array<string, array<string, mixed>>
     */
    private function findMentions(array $terms): array
    {
        if ($terms === []) {
            return [];
        }

        $items = [];

        foreach (UserDataMap::reviewTables() as $table => $config) {
            $rows = DB::table($table)->where(function ($query) use ($config, $terms) {
                foreach ($config['fields'] as $field) {
                    foreach ($terms as $term) {
                        $query->orWhere($field, 'like', "%{$term}%");
                    }
                }
            })->get();

            foreach ($rows as $row) {
                $items["{$table}:{$row->id}"] = $this->reviewItem($table, $row, GdprReviewItem::REASON_MENTION);
            }
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewItem(string $table, object $row, string $reason): array
    {
        $label = UserDataMap::reviewTables()[$table]['label']." #{$row->id}";

        if (isset($row->position)) {
            $label .= " · {$row->position}";
        }

        if (isset($row->session_date)) {
            $label .= " · {$row->session_date}";
        }

        return [
            'table_name' => $table,
            'record_id' => $row->id,
            'label' => $label,
            'reason' => $reason,
        ];
    }

    private function removeFromVatEud(int $vatsimId, bool $isVisitor): void
    {
        if (! $this->vatEudService->removeRosterAndEndorsements($vatsimId)) {
            Log::warning('GDPR removal: VatEUD roster/endorsement removal reported failure', ['vatsim_id' => $vatsimId]);
        }

        if ($isVisitor) {
            $this->deleteVisitorFromVatEud($vatsimId);
        }
    }

    private function deleteVisitorFromVatEud(int $vatsimId): void
    {
        $eudToken = config('services.vateud.token');

        if (! $eudToken) {
            Log::warning('VatEUD token not configured, skipping VatEUD visitor deletion', ['vatsim_id' => $vatsimId]);

            return;
        }

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $eudToken,
                'Accept' => 'application/json',
            ])
                ->timeout(10)
                ->delete("https://core.vateud.net/api/facility/visitors/{$vatsimId}/delete");

            if (! $response->successful()) {
                Log::warning('VatEUD visitor deletion returned non-success status', [
                    'vatsim_id' => $vatsimId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete visitor from VatEUD', ['vatsim_id' => $vatsimId, 'error' => $e->getMessage()]);
        }
    }
}
