<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MentorCourseResponseBuilder
{
    public function buildCourseData(Course $course, User $user): array
    {
        $trainees = DB::table('course_trainees')
            ->join('users', 'course_trainees.user_id', '=', 'users.id')
            ->where('course_trainees.course_id', $course->id)
            ->whereNull('course_trainees.completed_at')
            ->select(
                'users.id',
                'users.vatsim_id',
                'users.first_name',
                'users.last_name',
                'course_trainees.claimed_by_mentor_id',
                'course_trainees.remarks',
                'course_trainees.custom_order',
                'course_trainees.created_at as enrolled_at',
            )
            ->orderBy('course_trainees.custom_order')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'vatsim_id' => $t->vatsim_id,
                'name' => $t->first_name . ' ' . $t->last_name,
                'claimed_by_mentor_id' => $t->claimed_by_mentor_id,
                'remark' => $t->remarks,
                'order' => $t->custom_order,
                'enrolled_at' => $t->enrolled_at,
            ]);

        return [
            'id'             => $course->id,
            'name'           => $course->name,
            'position'       => $course->position,
            'type'           => $course->type,
            'soloStation'    => $course->solo_station,
            'activeTrainees' => $trainees->count(),
            'trainees' => $trainees->values(),
            'loaded' => true,
        ];
    }
}