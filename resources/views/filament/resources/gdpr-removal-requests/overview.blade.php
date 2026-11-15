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
        <p style="margin: 0; font-size: .875rem;">
            Anything matching <mark>one of these</mark> is highlighted yellow below, which makes it easy to spot:
            @foreach ($terms as $term)
                <code><mark>{{ $term }}</mark></code>
            @endforeach
        </p>

        <input type="search" x-model="q" placeholder="Search everything below..."
               class="fi-input" style="width: 100%; padding: .5rem .75rem; border: 1px solid rgba(128,128,128,.4); border-radius: .5rem; background: transparent;">

        <x-filament::section heading="Profile" :compact="true">
            <dl style="display: grid; grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); gap: .5rem 1.5rem; margin: 0;">
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

                <div style="padding: .75rem; display: flex; flex-direction: column; gap: .75rem;">
                    @forelse ($sections as $section)
                        <x-filament::section :collapsible="true" :collapsed="$kind === 'owned' && count($section['rows']) > 5" :compact="true">
                            <x-slot name="heading">
                                <x-filament::badge :color="$group['color']" style="display: inline-flex; margin-right: .5rem;">{{ $group['badge'] }}</x-filament::badge>
                                {{ $section['label'] }}
                                <span style="opacity: .6; font-weight: 400;">· {{ count($section['rows']) }}</span>
                            </x-slot>

                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; font-size: .85rem;">
                                    <thead>
                                        <tr>
                                            @foreach (array_keys($section['rows'][0]) as $column)
                                                <th style="text-align: left; padding: .25rem .5rem; opacity: .65; font-weight: 500;">{{ str($column)->replace('_', ' ')->title() }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($section['rows'] as $row)
                                            <tr x-show="!q || $el.textContent.toLowerCase().includes(q.toLowerCase())" style="border-top: 1px solid rgba(128,128,128,.2);">
                                                @foreach ($row as $value)
                                                    <td style="padding: .25rem .5rem; vertical-align: top; max-width: 28rem; overflow-wrap: anywhere;">{{ $highlight(\Illuminate\Support\Str::limit(strip_tags($format($value)), 200)) }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </x-filament::section>
                    @empty
                        <p style="margin: 0; opacity: .65; font-size: .875rem;">Nothing in this group.</p>
                    @endforelse
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
            <span style="display: block; margin-top: .35rem; font-size: .875rem;">The homepage keeps getting "pending" until every record is reviewed.</span>
        </div>
    </div>
@else
    <p style="margin: 0;">Completed {{ $request->completed_at?->format('Y-m-d H:i') }}. The homepage is answered with success.</p>
@endif
