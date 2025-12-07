<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log an activity with basic information
     */
    public static function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
        ?int $userId = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'model_type' => $subject ? get_class($subject) : null,
            'model_id' => $subject?->id,
            'properties' => $properties,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log a model change with detailed before/after data
     */
    public static function logModelChange(
        string $action,
        Model $model,
        ?Model $causer = null,
        array $old = [],
        array $new = []
    ): ActivityLog {
        $changes = self::getChanges($old, $new);

        return self::log(
            $action,
            $model,
            self::generateChangeDescription($action, $model, $changes, $causer),
            [
                'old' => $old,
                'new' => $new,
                'changes' => $changes,
                'causer_type' => $causer ? get_class($causer) : null,
                'causer_id' => $causer?->id,
                'causer_name' => $causer?->name ?? null,
            ],
            $causer?->id
        );
    }

    /**
     * Calculate what changed between old and new data
     */
    protected static function getChanges(array $old, array $new): array
    {
        $changes = [];

        foreach ($new as $key => $value) {
            $oldValue = $old[$key] ?? null;

            if ($oldValue != $value) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }

    /**
     * Generate a human-readable description of changes
     */
    protected static function generateChangeDescription(string $action, Model $model, array $changes, ?Model $causer): string
    {
        $modelName = class_basename($model);
        $userName = $causer?->name ?? Auth::user()?->name ?? 'System';

        if (empty($changes)) {
            return "{$userName} {$action} {$modelName} #{$model->id}";
        }

        $changedFields = implode(', ', array_keys($changes));
        return "{$userName} {$action} {$modelName} #{$model->id} (changed: {$changedFields})";
    }

    /**
     * Log waiting list joined - UPDATED to use WaitingListEntry as subject
     */
    public static function waitingListJoined(Model $waitingListEntry, Model $course, Model $user): void
    {
        self::log(
            'waiting_list.joined',
            $waitingListEntry,
            "{$user->name} joined waiting list for {$course->name}",
            [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'position_in_queue' => $waitingListEntry->position_in_queue ?? null,
                'activity' => $waitingListEntry->activity ?? 0,
                'date_added' => $waitingListEntry->date_added?->toIso8601String(),
            ],
            $user->id
        );
    }

    /**
     * Log waiting list left - UPDATED to use WaitingListEntry as subject
     */
    public static function waitingListLeft(Model $waitingListEntry, Model $course, Model $user): void
    {
        self::log(
            'waiting_list.left',
            $waitingListEntry,
            "{$user->name} left waiting list for {$course->name}",
            [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'position_in_queue' => $waitingListEntry->position_in_queue ?? null,
                'days_waited' => $waitingListEntry->date_added ? now()->diffInDays($waitingListEntry->date_added) : null,
                'activity' => $waitingListEntry->activity ?? 0,
            ],
            $user->id
        );
    }

    public static function trainingStarted(Model $course, Model $trainee, Model $mentor): void
    {
        self::log(
            'training.started',
            $course,
            "{$mentor->name} started training for {$trainee->name} in {$course->name}",
            [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
            ],
            $mentor->id
        );
    }

    public static function endorsementGranted(string $position, Model $trainee, Model $mentor, string $type = 'tier1'): void
    {
        self::log(
            "endorsement.{$type}.granted",
            $trainee,
            "{$mentor->name} granted {$position} endorsement to {$trainee->name}",
            [
                'position' => $position,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
                'type' => $type,
            ],
            $mentor->id
        );
    }

    public static function endorsementRemoved(string $position, Model $trainee, Model $mentor, string $reason = null): void
    {
        self::log(
            'endorsement.removed',
            $trainee,
            "{$mentor->name} started the removal process for {$position} endorsement from {$trainee->name}",
            [
                'position' => $position,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
                'reason' => $reason,
            ],
            $mentor->id
        );
    }

    public static function soloGranted(string $position, Model $trainee, Model $mentor, string $expiryDate): void
    {
        self::log(
            'solo.granted',
            $trainee,
            "{$mentor->name} granted solo endorsement for {$position} to {$trainee->name} (expires: {$expiryDate})",
            [
                'position' => $position,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
                'expiry_date' => $expiryDate,
            ],
            $mentor->id
        );
    }

    public static function soloExtended(string $position, Model $trainee, Model $mentor, string $newExpiryDate): void
    {
        self::log(
            'solo.extended',
            $trainee,
            "{$mentor->name} extended solo endorsement for {$position} for {$trainee->name} (new expiry: {$newExpiryDate})",
            [
                'position' => $position,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
                'new_expiry_date' => $newExpiryDate,
            ],
            $mentor->id
        );
    }

    public static function soloRemoved(string $position, Model $trainee, Model $mentor): void
    {
        self::log(
            'solo.removed',
            $trainee,
            "{$mentor->name} removed solo endorsement for {$position} from {$trainee->name}",
            [
                'position' => $position,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
            ],
            $mentor->id
        );
    }

    public static function traineeClaimed(Model $course, Model $trainee, Model $mentor): void
    {
        self::log(
            'trainee.claimed',
            $course,
            "{$mentor->name} claimed {$trainee->name} for {$course->name}",
            [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
            ],
            $mentor->id
        );
    }

    public static function traineeUnclaimed(Model $course, Model $trainee, Model $mentor): void
    {
        self::log(
            'trainee.unclaimed',
            $course,
            "{$mentor->name} unclaimed {$trainee->name} from {$course->name}",
            [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
            ],
            $mentor->id
        );
    }

    public static function traineeAssigned(Model $course, Model $trainee, Model $newMentor, Model $assigningMentor): void
    {
        self::log(
            'trainee.assigned',
            $course,
            "{$assigningMentor->name} assigned {$trainee->name} to {$newMentor->name} for {$course->name}",
            [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'new_mentor_id' => $newMentor->id,
                'new_mentor_name' => $newMentor->name,
                'assigning_mentor_id' => $assigningMentor->id,
                'assigning_mentor_name' => $assigningMentor->name,
            ],
            $assigningMentor->id
        );
    }

    public static function courseFinished(Model $course, Model $trainee, Model $mentor): void
    {
        self::log(
            'course.finished',
            $course,
            "{$mentor->name} marked {$course->name} as finished for {$trainee->name}",
            [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
            ],
            $mentor->id
        );
    }

    public static function traineeRemoved(Model $course, Model $trainee, Model $mentor): void
    {
        self::log(
            'trainee.removed',
            $course,
            "{$mentor->name} removed {$trainee->name} from {$course->name}",
            [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
            ],
            $mentor->id
        );
    }

    public static function traineeReactivated(Model $course, Model $trainee, Model $mentor): void
    {
        self::log(
            'trainee.reactivated',
            $course,
            "{$mentor->name} reactivated {$trainee->name} for {$course->name}",
            [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
            ],
            $mentor->id
        );
    }

    public static function mentorAdded(Model $course, Model $newMentor, Model $addingUser): void
    {
        self::log(
            'mentor.added',
            $course,
            "{$addingUser->name} added {$newMentor->name} as mentor for {$course->name}",
            [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'new_mentor_id' => $newMentor->id,
                'new_mentor_name' => $newMentor->name,
                'adding_user_id' => $addingUser->id,
                'adding_user_name' => $addingUser->name,
            ],
            $addingUser->id
        );
    }

    public static function mentorRemoved(Model $course, Model $removedMentor, Model $removingUser): void
    {
        self::log(
            'mentor.removed',
            $course,
            "{$removingUser->name} removed {$removedMentor->name} as mentor from {$course->name}",
            [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'removed_mentor_id' => $removedMentor->id,
                'removed_mentor_name' => $removedMentor->name,
                'removing_user_id' => $removingUser->id,
                'removing_user_name' => $removingUser->name,
            ],
            $removingUser->id
        );
    }

    public static function remarksUpdated(Model $course, Model $trainee, Model $mentor, string $newRemarks): void
    {
        self::log(
            'remarks.updated',
            $course,
            "{$mentor->name} updated remarks for {$trainee->name} in {$course->name}",
            [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
                'remarks_length' => strlen($newRemarks),
            ],
            $mentor->id
        );
    }

    public static function traineeAddedToCourse(
        Model $course,
        Model $trainee,
        Model $mentor,
        bool $wasReactivated = false
    ): void {
        self::log(
            'trainee.added_to_course',
            $course,
            "{$mentor->name} added {$trainee->name} to {$course->name}" .
            ($wasReactivated ? " (reactivated)" : ""),
            [
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
                'was_reactivated' => $wasReactivated,
            ],
            $mentor->id
        );
    }

    public static function familiarisationAdded(
        Model $trainee,
        string $sectorName,
        int $sectorId,
        string $fir,
        Model $mentor,
        ?Model $course = null,
        bool $viaCourseCompletion = false
    ): void {
        $description = "{$mentor->name} granted {$sectorName} ({$fir}) familiarisation to {$trainee->name}";
        if ($viaCourseCompletion && $course) {
            $description .= " via {$course->name} completion";
        }

        $properties = [
            'trainee_id' => $trainee->id,
            'trainee_name' => $trainee->name,
            'sector_id' => $sectorId,
            'sector_name' => $sectorName,
            'fir' => $fir,
            'mentor_id' => $mentor->id,
            'mentor_name' => $mentor->name,
            'via_course_completion' => $viaCourseCompletion,
        ];

        if ($course) {
            $properties['course_id'] = $course->id;
            $properties['course_name'] = $course->name;
        }

        self::log(
            'familiarisation.added',
            $trainee,
            $description,
            $properties,
            $mentor->id
        );
    }

    /**
     * Log core theory test assignment
     */
    public static function coreTestAssigned(Model $trainee, Model $course, Model $mentor): void
    {
        self::log(
            'core_test.assigned',
            $trainee,
            "{$mentor->name} assigned core theory test for {$course->position} to {$trainee->name}",
            [
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'position' => $course->position,
                'mentor_id' => $mentor->id,
                'mentor_name' => $mentor->name,
            ],
            $mentor->id
        );
    }

    public static function cptCreated(Model $cpt, Model $course, Model $trainee, Model $creator, ?Model $examiner = null, ?Model $local = null): void
    {
        $description = "{$creator->name} created CPT for {$trainee->name} in {$course->name}";
        if ($examiner) {
            $description .= " with examiner {$examiner->name}";
        }
        if ($local) {
            $description .= " and local mentor {$local->name}";
        }

        $properties = [
            'cpt_id' => $cpt->id,
            'trainee_id' => $trainee->id,
            'trainee_name' => $trainee->name,
            'course_id' => $course->id,
            'course_name' => $course->name,
            'position' => $course->position,
            'date' => $cpt->date?->toIso8601String(),
            'creator_id' => $creator->id,
            'creator_name' => $creator->name,
        ];

        if ($examiner) {
            $properties['examiner_id'] = $examiner->id;
            $properties['examiner_name'] = $examiner->name;
        }

        if ($local) {
            $properties['local_id'] = $local->id;
            $properties['local_name'] = $local->name;
        }

        self::log(
            'cpt.created',
            $cpt,
            $description,
            $properties,
            $creator->id
        );
    }

    public static function cptExaminerJoined(Model $cpt, Model $course, Model $trainee, Model $examiner): void
    {
        self::log(
            'cpt.examiner_joined',
            $cpt,
            "{$examiner->name} signed up as examiner for {$trainee->name}'s CPT in {$course->name}",
            [
                'cpt_id' => $cpt->id,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'position' => $course->position,
                'examiner_id' => $examiner->id,
                'examiner_name' => $examiner->name,
                'date' => $cpt->date?->toIso8601String(),
            ],
            $examiner->id
        );
    }

    public static function cptExaminerLeft(Model $cpt, Model $course, Model $trainee, Model $examiner): void
    {
        self::log(
            'cpt.examiner_left',
            $cpt,
            "{$examiner->name} cancelled as examiner for {$trainee->name}'s CPT in {$course->name}",
            [
                'cpt_id' => $cpt->id,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'position' => $course->position,
                'examiner_id' => $examiner->id,
                'examiner_name' => $examiner->name,
                'date' => $cpt->date?->toIso8601String(),
            ],
            $examiner->id
        );
    }

    public static function cptLocalJoined(Model $cpt, Model $course, Model $trainee, Model $local): void
    {
        self::log(
            'cpt.local_joined',
            $cpt,
            "{$local->name} signed up as local mentor for {$trainee->name}'s CPT in {$course->name}",
            [
                'cpt_id' => $cpt->id,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'position' => $course->position,
                'local_id' => $local->id,
                'local_name' => $local->name,
                'date' => $cpt->date?->toIso8601String(),
            ],
            $local->id
        );
    }

    public static function cptLocalLeft(Model $cpt, Model $course, Model $trainee, Model $local): void
    {
        self::log(
            'cpt.local_left',
            $cpt,
            "{$local->name} cancelled as local mentor for {$trainee->name}'s CPT in {$course->name}",
            [
                'cpt_id' => $cpt->id,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'position' => $course->position,
                'local_id' => $local->id,
                'local_name' => $local->name,
                'date' => $cpt->date?->toIso8601String(),
            ],
            $local->id
        );
    }

    public static function cptLogUploaded(Model $cptLog, Model $cpt, Model $course, Model $trainee, Model $uploader): void
    {
        self::log(
            'cpt.log_uploaded',
            $cpt,
            "{$uploader->name} uploaded CPT log for {$trainee->name}'s CPT in {$course->name}",
            [
                'cpt_id' => $cpt->id,
                'cpt_log_id' => $cptLog->id,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'position' => $course->position,
                'uploader_id' => $uploader->id,
                'uploader_name' => $uploader->name,
                'file_name' => $cptLog->file_name,
                'date' => $cpt->date?->toIso8601String(),
            ],
            $uploader->id
        );
    }

    public static function cptGraded(Model $cpt, Model $course, Model $trainee, Model $grader, bool $passed): void
    {
        $result = $passed ? 'passed' : 'failed';

        self::log(
            "cpt.graded_{$result}",
            $cpt,
            "{$grader->name} marked {$trainee->name}'s CPT in {$course->name} as {$result}",
            [
                'cpt_id' => $cpt->id,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'position' => $course->position,
                'grader_id' => $grader->id,
                'grader_name' => $grader->name,
                'result' => $result,
                'passed' => $passed,
                'date' => $cpt->date?->toIso8601String(),
            ],
            $grader->id
        );
    }

    public static function cptDeleted(Model $cpt, Model $course, Model $trainee, Model $deleter): void
    {
        self::log(
            'cpt.deleted',
            $cpt,
            "{$deleter->name} deleted CPT for {$trainee->name} in {$course->name}",
            [
                'cpt_id' => $cpt->id,
                'trainee_id' => $trainee->id,
                'trainee_name' => $trainee->name,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'position' => $course->position,
                'deleter_id' => $deleter->id,
                'deleter_name' => $deleter->name,
                'date' => $cpt->date?->toIso8601String(),
                'was_graded' => $cpt->passed !== null,
                'had_log' => $cpt->log_uploaded,
            ],
            $deleter->id
        );
    }
}