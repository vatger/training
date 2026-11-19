@php
    $current = $request->isPending() ? 1 : ($request->isApproved() ? 2 : 3);
    $steps = [
        1 => ['title' => 'Approve', 'text' => 'You decide whether the user is deleted. Approving deletes everything marked "Deleted" below. Nothing else is touched.'],
        2 => ['title' => 'Review', 'text' => 'Only for the "Kept" records of other users. Remove anything that identifies the user, then mark each record as reviewed.'],
        3 => ['title' => 'Completed', 'text' => 'Everything is deleted and reviewed. Only the VATSIM ID stays as proof.'],
    ];
@endphp

@if ($request->isCancelled())
    <p style="margin: 0;">Cancelled {{ $request->cancelled_at?->format('Y-m-d H:i') }}. The homepage stopped polling this user for {{ \App\Models\GdprRemovalRequest::STALE_AFTER_DAYS }} days, so nothing was deleted. If it asks again, a new request is opened.</p>
@else
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); gap: .75rem; margin-bottom: 1rem;">
    @foreach ($steps as $number => $step)
        @php
            $state = $number < $current ? 'done' : ($number === $current ? 'current' : 'todo');
            $badgeColor = ['done' => 'success', 'current' => 'primary', 'todo' => 'gray'][$state];
            $borderColor = $state === 'current' ? 'rgb(var(--primary-500))' : 'rgba(128,128,128,.3)';
            $opacity = $state === 'todo' ? '.6' : '1';
        @endphp
        <div style="padding: .75rem 1rem; border-radius: .5rem; border: 2px solid {{ $borderColor }}; opacity: {{ $opacity }};">
            <div style="display: flex; align-items: center; gap: .5rem;">
                <x-filament::badge :color="$badgeColor" style="display: inline-flex;">
                    {{ $state === 'done' ? '✓' : $number }}
                </x-filament::badge>
                <strong>{{ $step['title'] }}</strong>
                @if ($state === 'current')
                    <span style="font-size: .75rem; opacity: .7;">· you are here</span>
                @endif
            </div>
            <p style="margin: .35rem 0 0; font-size: .85rem; opacity: .85;">{{ $step['text'] }}</p>
        </div>
    @endforeach
</div>

@php
    $highlight = function (?string $text) use ($terms) {
        $safe = e($text ?? '');
        foreach ($terms as $term) {
            $safe = preg_replace('/('.preg_quote(e($term), '/').')/i', '<mark>$1</mark>', $safe);
        }

        return new \Illuminate\Support\HtmlString($safe);
    };

    $format = function ($value) {
        return match (true) {
            is_null($value) => '-',
            is_bool($value) => $value ? 'yes' : 'no',
            is_array($value) => json_encode($value),
            default => (string) $value,
        };
    };
@endphp

@if ($request->isPending() && $data)
    <div x-data="{ q: '' }" style="display: flex; flex-direction: column; gap: 1rem;">

        <input type="search" x-model="q" placeholder="Search everything below..."
               class="fi-input" style="width: 100%; padding: .5rem .75rem; border: 1px solid rgba(128,128,128,.4); border-radius: .5rem; background: transparent;">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(22rem, 1fr)); gap: .75rem;">
            <div style="padding: .75rem 1rem; border-left: 4px solid rgb(var(--danger-500)); background: rgba(128,128,128,.08); border-radius: .5rem;">
                <x-filament::badge color="danger" style="display: inline-flex;">Deleted</x-filament::badge>
                <strong style="margin-left: .35rem;">Removed permanently on approval</strong>
                <p style="margin: .35rem 0 0; font-size: .85rem; opacity: .85;">Everything that belongs to this user (their own trainings, CPTs, enrolments, waiting lists, profile). It cannot be restored.</p>
            </div>
            <div style="padding: .75rem 1rem; border-left: 4px solid rgb(var(--info-500)); background: rgba(128,128,128,.08); border-radius: .5rem;">
                <x-filament::badge color="info" style="display: inline-flex;">Kept</x-filament::badge>
                <strong style="margin-left: .35rem;">Stays because it belongs to other users</strong>
                <p style="margin: .35rem 0 0; font-size: .85rem; opacity: .85;">Records of other people that this user wrote (e.g. as mentor or examiner). The user is replaced by "GDPR Removed" and you review the text for leftover personal data afterwards.</p>
            </div>
        </div>

        <x-filament::section heading="Profile" :compact="true">
            <x-slot name="description"><x-filament::badge color="danger" style="display: inline-flex;">Deleted</x-filament::badge> The profile is removed together with the user.</x-slot>
            <dl style="display: grid; grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr)); gap: .5rem 1.5rem; margin: 0;">
                @foreach ($data['profile'] as $key => $value)
                    <div x-show="!q || $el.textContent.toLowerCase().includes(q.toLowerCase())">
                        <dt style="font-size: .75rem; opacity: .65;">{{ str($key)->replace('_', ' ')->title() }}</dt>
                        <dd style="margin: 0;">{{ $highlight($format($value)) }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-filament::section>

        @foreach ([
            'owned' => ['title' => 'WILL BE DELETED', 'hint' => 'Belongs to this user. Removed for good when you approve.', 'color' => 'danger', 'badge' => 'Deleted'],
            'authored' => ['title' => 'WILL BE KEPT', 'hint' => 'Belongs to other users. The user is replaced by "GDPR Removed" and the text is checked afterwards.', 'color' => 'info', 'badge' => 'Kept'],
        ] as $kind => $group)
            @php($sections = collect($data['sections'])->where('kind', $kind)->filter(fn ($section) => count($section['rows']) > 0))

            <div style="border: 2px solid rgb(var(--{{ $group['color'] }}-500)); border-radius: .75rem; overflow: hidden;">
                <div style="background: rgb(var(--{{ $group['color'] }}-500)); color: #fff; padding: .6rem 1rem;">
                    <strong style="letter-spacing: .05em;">{{ $group['title'] }}</strong>
                    <span style="margin-left: .75rem; font-size: .85rem; opacity: .95;">{{ $group['hint'] }}</span>
                </div>

                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    @forelse ($sections as $section)
                        <x-filament::section :collapsible="true" :collapsed="$kind === 'owned' && count($section['rows']) > 5" :compact="true">
                            <x-slot name="heading">
                                <x-filament::badge :color="$group['color']" style="display: inline-flex; margin-right: .5rem;">{{ $group['badge'] }}</x-filament::badge>
                                {{ $section['label'] }}
                                <span style="opacity: .6; font-weight: 400;">· {{ count($section['rows']) }}</span>
                            </x-slot>

                            <p style="margin: 0 0 .5rem; font-size: .8rem; opacity: .7;">
                                {{ $kind === 'owned' ? 'All '.count($section['rows']).' row(s) below will be deleted.' : 'These '.count($section['rows']).' row(s) are kept, with this user shown as "GDPR Removed".' }}
                            </p>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; font-size: .9rem;">
                                    <thead>
                                        <tr>
                                            @foreach (array_keys($section['rows'][0]) as $column)
                                                <th style="text-align: left; padding: .4rem .75rem; opacity: .65; font-weight: 500;">{{ str($column)->replace('_', ' ')->title() }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($section['rows'] as $row)
                                            <tr x-show="!q || $el.textContent.toLowerCase().includes(q.toLowerCase())" style="border-top: 1px solid rgba(128,128,128,.2);">
                                                @foreach ($row as $value)
                                                    <td style="padding: .4rem .75rem; vertical-align: top; min-width: 8rem; max-width: 40rem; overflow-wrap: anywhere;">{{ $highlight(\Illuminate\Support\Str::limit(strip_tags($format($value)), 200)) }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </x-filament::section>
                    @empty
                        @if ($kind !== 'authored' || count($mentions) === 0)
                            <p style="margin: 0; opacity: .65; font-size: .875rem;">Nothing in this group.</p>
                        @endif
                    @endforelse

                    @if ($kind === 'authored' && count($mentions) > 0)
                        <x-filament::section :collapsible="true" :compact="true">
                            <x-slot name="heading">
                                <x-filament::badge color="warning" style="display: inline-flex; margin-right: .5rem;">Kept</x-filament::badge>
                                Records whose text mentions the user
                                <span style="opacity: .6; font-weight: 400;">· {{ count($mentions) }}</span>
                            </x-slot>
                            <p style="margin: 0 0 .5rem; font-size: .8rem; opacity: .7;">Somebody else's records that contain the name, VATSIM ID or email in their text. They are kept unchanged and need a manual review after approval.</p>
                            <table style="width: 100%; border-collapse: collapse; font-size: .9rem;">
                                <thead><tr>
                                    <th style="text-align: left; padding: .4rem .75rem; opacity: .65; font-weight: 500;">Record</th>
                                    <th style="text-align: left; padding: .4rem .75rem; opacity: .65; font-weight: 500;">Matching text</th>
                                </tr></thead>
                                <tbody>
                                    @foreach ($mentions as $mention)
                                        <tr x-show="!q || $el.textContent.toLowerCase().includes(q.toLowerCase())" style="border-top: 1px solid rgba(128,128,128,.2);">
                                            <td style="padding: .4rem .75rem; vertical-align: top; white-space: nowrap;">{{ $mention['label'] }} #{{ $mention['row']->id }}</td>
                                            <td style="padding: .4rem .75rem; vertical-align: top; overflow-wrap: anywhere;">
                                                @foreach ($mention['fields'] as $field)
                                                    <div><span style="opacity: .65;">{{ str($field)->replace('_', ' ')->title() }}:</span> {{ $highlight(\Illuminate\Support\Str::limit(strip_tags($mention['row']->{$field}), 200)) }}</div>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </x-filament::section>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@elseif ($request->isPending())
    <p>The user no longer exists in this system.</p>
@elseif ($request->isApproved())
    <div style="display: flex; flex-direction: column; gap: .75rem;">
        <div style="padding: .6rem 1rem; border-left: 4px solid rgb(var(--danger-500)); background: rgba(128,128,128,.08); border-radius: .5rem;">
            <strong>Already deleted</strong>
            <span style="font-size: .875rem;">· The user and all of their own data are gone (approved {{ $request->approved_at?->format('Y-m-d H:i') }} by {{ $request->approvedBy?->name ?? 'a former user' }}). Nothing more to do there.</span>
        </div>
        <div style="padding: .6rem 1rem; border-left: 4px solid rgb(var(--info-500)); background: rgba(128,128,128,.08); border-radius: .5rem;">
            <strong>Kept, needs your review</strong>
            <span style="font-size: .875rem;">· The records below belong to other users and stay. Open each one, remove anything that identifies the removed user ({{ $request->search_terms ? implode(', ', $request->search_terms) : 'name, VATSIM ID, email' }}) and mark it as reviewed.</span>
            <ul style="margin: .35rem 0 0 1.25rem; padding: 0; font-size: .875rem; list-style: disc;">
                <li><strong>Written by the user</strong>: the user was the author. They are now shown as "GDPR Removed", but the text may still name them.</li>
                <li><strong>Text mentions the user</strong>: somebody else's record whose text contains the name, VATSIM ID or email.</li>
            </ul>
        </div>
    </div>
@else
    <p style="margin: 0;">Completed {{ $request->completed_at?->format('Y-m-d H:i') }}</p>
@endif
@endif
