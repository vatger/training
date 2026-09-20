@php
    $formatValue = function ($value) {
        if (is_null($value)) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    };
@endphp

@if (! empty($changes))
    <div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl border border-gray-200 dark:divide-white/10 dark:border-white/10">
        <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/10">
            <thead>
                <tr class="bg-gray-50 dark:bg-white/5">
                    <th class="px-4 py-2 text-start text-xs font-semibold text-gray-600 dark:text-gray-300">Field</th>
                    <th class="px-4 py-2 text-start text-xs font-semibold text-gray-600 dark:text-gray-300">Old Value</th>
                    <th class="px-4 py-2 text-start text-xs font-semibold text-gray-600 dark:text-gray-300">New Value</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($changes as $field => $change)
                    <tr>
                        <td class="px-4 py-2 align-top text-sm font-medium text-gray-950 dark:text-white">{{ $field }}</td>
                        <td class="px-4 py-2 align-top text-sm text-danger-600 dark:text-danger-400">
                            <pre class="whitespace-pre-wrap break-words font-mono text-xs">{{ $formatValue($change['old'] ?? null) }}</pre>
                        </td>
                        <td class="px-4 py-2 align-top text-sm text-success-600 dark:text-success-400">
                            <pre class="whitespace-pre-wrap break-words font-mono text-xs">{{ $formatValue($change['new'] ?? null) }}</pre>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif (! empty($old) || ! empty($new))
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <p class="mb-2 text-xs font-semibold text-gray-600 dark:text-gray-300">Old</p>
            <pre class="whitespace-pre-wrap break-words rounded-lg bg-gray-50 p-3 font-mono text-xs text-gray-950 dark:bg-white/5 dark:text-white">{{ $formatValue($old) }}</pre>
        </div>
        <div>
            <p class="mb-2 text-xs font-semibold text-gray-600 dark:text-gray-300">New</p>
            <pre class="whitespace-pre-wrap break-words rounded-lg bg-gray-50 p-3 font-mono text-xs text-gray-950 dark:bg-white/5 dark:text-white">{{ $formatValue($new) }}</pre>
        </div>
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">No change data available.</p>
@endif
