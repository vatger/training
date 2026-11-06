<?php

namespace App\Services\TeamOverview;

use App\Models\ChiefOfTraining;
use App\Models\Examiner;
use App\Models\LeadingMentor;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TeamOverviewData
{
    /** Section key => [mentor role, FIR code] */
    private const FIRS = [
        'bremen' => ['EDWW Mentor', 'EDWW'],
        'langen' => ['EDGG Mentor', 'EDGG'],
        'muenchen' => ['EDMM Mentor', 'EDMM'],
    ];

    /**
     * Text lines per section key: `pruefer`, `bremen`, `langen`, `muenchen`.
     *
     * @return array<string, list<string>>
     */
    public function sections(): array
    {
        $sections = ['pruefer' => $this->pruefer()];

        foreach (self::FIRS as $key => [$role, $fir]) {
            $sections[$key] = $this->fir($role, $fir);
        }

        return $sections;
    }

    /** @return list<string> */
    private function pruefer(): array
    {
        return Examiner::with('user')
            ->get()
            ->sortBy('callsign', SORT_NATURAL)
            ->map(fn (Examiner $examiner) => "{$examiner->callsign} - {$examiner->user->full_name}".$this->stations($examiner))
            ->values()
            ->all();
    }

    private function stations(Examiner $examiner): string
    {
        $stations = array_values(array_intersect(Examiner::VALID_POSITIONS, $examiner->positions ?? []));

        return $stations === [] ? '' : ', '.implode('/', $stations);
    }

    /** @return list<string> */
    private function fir(string $role, string $fir): array
    {
        $leadingMentorIds = LeadingMentor::where('fir', $fir)->pluck('user_id');

        $users = User::query()
            ->where(fn ($query) => $query
                ->whereHas('roles', fn ($roles) => $roles->where('name', $role))
                ->orWhereIn('id', $leadingMentorIds))
            ->get();

        $examinerIds = Examiner::pluck('user_id')->flip();
        $chiefs = ChiefOfTraining::with('course')->get()->groupBy('user_id');

        $rows = $users->map(fn (User $user) => [
            'name' => $user->full_name,
            'rank' => match (true) {
                $leadingMentorIds->contains($user->id) => 0,
                $chiefs->has($user->id) => 1,
                $examinerIds->has($user->id) => 2,
                default => 3,
            },
            'tags' => $this->tags($user, $leadingMentorIds, $examinerIds, $chiefs),
        ])->sort(fn ($a, $b) => [$a['rank'], Str::lower(Str::ascii($a['name']))] <=> [$b['rank'], Str::lower(Str::ascii($b['name']))]);

        $width = $rows->max(fn ($row) => mb_strlen($row['name'])) ?? 0;

        return $rows
            ->map(fn ($row) => $row['tags'] === ''
                ? $row['name']
                : mb_str_pad($row['name'], $width + 2).'('.$row['tags'].')')
            ->values()
            ->all();
    }

    private function tags(User $user, Collection $leadingMentorIds, Collection $examinerIds, Collection $chiefs): string
    {
        $tags = [];

        if ($examinerIds->has($user->id)) {
            $tags[] = 'TD';
        }

        if ($leadingMentorIds->contains($user->id)) {
            $tags[] = 'LM';
        }

        $cot = ($chiefs[$user->id] ?? collect())
            ->filter(fn (ChiefOfTraining $chief) => $chief->course !== null)
            ->groupBy(fn (ChiefOfTraining $chief) => $chief->course->airport_icao)
            ->map(fn ($group, $icao) => 'CoT '.$icao.' '.$group->pluck('course.position')->unique()->implode('+'));

        return implode(', ', [...$tags, ...$cot->values()]);
    }
}
