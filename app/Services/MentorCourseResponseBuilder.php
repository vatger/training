<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MentorCourseResponseBuilder
{
    public function build(Course $course, User $user, array $endorsements = []): array
    {
        $trainees = DB::table('course_trainees')
            ->join('users', 'course_trainees.user_id', '=', 'users.id')
            ->leftJoin('users as remark_authors', 'course_trainees.remark_author_id', '=', 'remark_authors.id')
            ->leftJoin('users as claimed_mentors', 'course_trainees.claimed_by_mentor_id', '=', 'claimed_mentors.id')
            ->where('course_trainees.course_id', $course->id)
            ->whereNull('course_trainees.completed_at')
            ->select(
                'users.id',
                'users.vatsim_id',
                'users.first_name',
                'users.last_name',
                'course_trainees.claimed_by_mentor_id',
                'claimed_mentors.first_name as claimed_first_name',
                'claimed_mentors.last_name as claimed_last_name',
                'course_trainees.remarks',
                'course_trainees.remark_updated_at',
                'remark_authors.first_name as author_first_name',
                'remark_authors.last_name as author_last_name',
                'course_trainees.custom_order',
            )
            ->orderBy('course_trainees.custom_order')
            ->get();

        $logs = DB::table('training_logs')
            ->where('course_id', $course->id)
            ->whereIn('trainee_id', $trainees->pluck('id')->toArray())
            ->select('trainee_id', 'result', 'session_date', 'next_step')
            ->orderBy('session_date')
            ->get()
            ->groupBy('trainee_id');

        return [
            'id'             => $course->id,
            'name'           => $course->name,
            'position'       => $course->position,
            'type'           => $course->type,
            'soloStation'    => $course->solo_station,
            'activeTrainees' => $trainees->count(),
            'loaded' => true,
            'trainees' => $trainees->map(fn($t) => $this->mapTrainee($t, $user, $logs, $endorsements))->values(),
        ];
    }

    private function mapTrainee(object $t, User $user, $logs, array $endorsements): array
    {
        $traineeLog = $logs->get($t->id, collect());
        $latest = $traineeLog->last();

        return [
            'id' => $t->id,
            'vatsimId' => $t->vatsim_id,
            'name' => $t->first_name . ' ' . $t->last_name,
            'claimedBy' => $this->resolveClaimedBy($t, $user),
            'claimedByMentorId' => $t->claimed_by_mentor_id,
            'progress' => $traineeLog->filter(fn($l) => $l->result !== null)->map(fn($l) => (bool) $l->result)->values()->toArray(),
            'lastSession' => $latest?->session_date,
            'nextStep' => $latest?->next_step ?? '',
            'remark' => $t->remarks ? [
                'text' => $t->remarks,
                'updated_at' => $t->remark_updated_at,
                'author_name' => $t->author_first_name ? $t->author_first_name . ' ' . $t->author_last_name : null,
            ] : null,
            'soloStatus' => $endorsements[$t->vatsim_id]['soloStatus'] ?? null,
            'endorsementStatus' => $endorsements[$t->vatsim_id]['endorsementStatus'] ?? null,
        ];
    }

    private function resolveClaimedBy(object $t, User $user): ?string
    {
        if (!$t->claimed_by_mentor_id)
            return null;
        return $t->claimed_by_mentor_id === $user->id
            ? 'You'
            : $t->claimed_first_name . ' ' . $t->claimed_last_name;
    }
}