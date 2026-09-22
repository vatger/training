<?php

namespace App\Enums;

enum ActivityAction: string
{
    case WAITING_LIST_JOINED = 'waiting_list.joined';
    case WAITING_LIST_LEFT = 'waiting_list.left';
    case WAITING_LIST_ENTRY_CREATED = 'waitinglistentry.created';
    case WAITING_LIST_ENTRY_DELETED = 'waitinglistentry.deleted';

    case TRAINING_STARTED = 'training.started';
    case COURSE_FINISHED = 'course.finished';

    case ENDORSEMENT_TIER1_GRANTED = 'endorsement.tier1.granted';
    case ENDORSEMENT_TIER2_GRANTED = 'endorsement.tier2.granted';
    case ENDORSEMENT_REMOVED = 'endorsement.removed';
    case ENDORSEMENT_NOTIFIED = 'endorsement.notified';
    case ENDORSEMENT_DELETED = 'endorsement.deleted';

    case SOLO_GRANTED = 'solo.granted';
    case SOLO_EXTENDED = 'solo.extended';
    case SOLO_REMOVED = 'solo.removed';
    case CORE_TEST_ASSIGNED = 'core_test.assigned';

    case TRAINEE_CLAIMED = 'trainee.claimed';
    case TRAINEE_UNCLAIMED = 'trainee.unclaimed';
    case TRAINEE_ASSIGNED = 'trainee.assigned';
    case TRAINEE_REMOVED = 'trainee.removed';
    case TRAINEE_REACTIVATED = 'trainee.reactivated';
    case TRAINEE_ADDED_TO_COURSE = 'trainee.added_to_course';

    case MENTOR_ADDED = 'mentor.added';
    case MENTOR_REMOVED = 'mentor.removed';

    case REMARKS_UPDATED = 'remarks.updated';

    case FAMILIARISATION_ADDED = 'familiarisation.added';
    case FAMILIARISATION_CREATED = 'familiarisation.created';
    case FAMILIARISATION_UPDATED = 'familiarisation.updated';
    case FAMILIARISATION_DELETED = 'familiarisation.deleted';

    case TRAININGLOG_ADDED = 'traininglog.added';
    case TRAININGLOG_CREATED = 'traininglog.created';
    case TRAININGLOG_UPDATED = 'traininglog.updated';
    case TRAININGLOG_REMOVED = 'traininglog.removed';
    case TRAININGLOG_DELETED = 'traininglog.deleted';

    case COURSE_CREATED = 'course.created';
    case COURSE_UPDATED = 'course.updated';
    case COURSE_DELETED = 'course.deleted';

    case COT_CREATED = 'chiefoftraining.created';
    case COT_UPDATED = 'chiefoftraining.updated';
    case COT_DELETED = 'chiefoftraining.deleted';

    case CPT_CREATED = 'cpt.created';
    case CPT_EXAMINER_JOINED = 'cpt.examiner_joined';
    case CPT_EXAMINER_LEFT = 'cpt.examiner_left';
    case CPT_LOCAL_JOINED = 'cpt.local_joined';
    case CPT_LOCAL_LEFT = 'cpt.local_left';
    case CPT_LOG_UPLOADED = 'cpt.log_uploaded';
    case CPT_PASSED = 'cpt.graded_passed';
    case CPT_FAILED = 'cpt.graded_failed';
    case CPT_DELETED = 'cpt.deleted';
    case CPT_UPDATED = 'cpt.updated';

    case ROSTER_REMOVED = 'roster.removed';
    case ROSTER_NOTIFIED = 'roster.notified';

    case GDPR_DELETION = 'gdpr.deletion';
    case API_USER_RETRIEVAL = 'api.user.retrieval';

    case USER_ROLES_UPDATED = 'user.roles_updated';
    case USER_PERMISSIONS_UPDATED = 'user.permissions_updated';
    case USER_COURSE_ENROLLMENT_ADDED = 'user.course_enrollment_added';
    case USER_COURSE_ENROLLMENT_REMOVED = 'user.course_enrollment_removed';
    case USER_COURSE_ENROLLMENT_UPDATED = 'user.course_enrollment_updated';

    case ROLE_PERMISSIONS_UPDATED = 'role.permissions_updated';

    case API_KEY_CREATED = 'apikey.created';
    case API_KEY_UPDATED = 'apikey.updated';
    case API_KEY_DELETED = 'apikey.deleted';

    case EXAMINER_CREATED = 'examiner.created';
    case EXAMINER_UPDATED = 'examiner.updated';
    case EXAMINER_DELETED = 'examiner.deleted';

    case FAMILIARISATION_SECTOR_CREATED = 'familiarisationsector.created';
    case FAMILIARISATION_SECTOR_UPDATED = 'familiarisationsector.updated';
    case FAMILIARISATION_SECTOR_DELETED = 'familiarisationsector.deleted';

    case LEADING_MENTOR_CREATED = 'leadingmentor.created';
    case LEADING_MENTOR_DELETED = 'leadingmentor.deleted';

    case ROLE_CREATED = 'role.created';
    case ROLE_UPDATED = 'role.updated';
    case ROLE_DELETED = 'role.deleted';

    case TIER2_ENDORSEMENT_CREATED = 'tier2endorsement.created';
    case TIER2_ENDORSEMENT_UPDATED = 'tier2endorsement.updated';
    case TIER2_ENDORSEMENT_DELETED = 'tier2endorsement.deleted';

    case ENDORSEMENT_ACTIVITY_UPDATED = 'endorsementactivity.updated';

    case WAITING_LIST_ENTRY_UPDATED = 'waitinglistentry.updated';

    case WAITING_LIST_RESTRICTION_CREATED = 'waitinglistrestriction.created';
    case WAITING_LIST_RESTRICTION_UPDATED = 'waitinglistrestriction.updated';
    case WAITING_LIST_RESTRICTION_DELETED = 'waitinglistrestriction.deleted';

    case USER_CREATED = 'user.created';
    case USER_UPDATED = 'user.updated';
    case USER_DELETED = 'user.deleted';

    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';

    public function getLabel(): string
    {
        return match ($this) {
            self::WAITING_LIST_JOINED => 'Joined Waiting List',
            self::WAITING_LIST_LEFT => 'Left Waiting List',
            self::WAITING_LIST_ENTRY_CREATED => 'Create Waiting List Entry',
            self::WAITING_LIST_ENTRY_DELETED => 'Deleted Waiting List Entry',

            self::TRAINING_STARTED => 'Training Started',
            self::COURSE_FINISHED => 'Course Finished',

            self::ENDORSEMENT_TIER1_GRANTED => 'Tier 1 Endorsement Granted',
            self::ENDORSEMENT_TIER2_GRANTED => 'Tier 2 Endorsement Granted',
            self::ENDORSEMENT_REMOVED => 'Endorsement Removed',
            self::ENDORSEMENT_NOTIFIED => 'Notified Removal',
            self::ENDORSEMENT_DELETED => 'Endorsement Deleted',

            self::SOLO_GRANTED => 'Solo Endorsement Granted',
            self::SOLO_EXTENDED => 'Solo Endorsement Extended',
            self::SOLO_REMOVED => 'Solo Endorsement Removed',
            self::CORE_TEST_ASSIGNED => 'Core Test Assigned',

            self::TRAINEE_CLAIMED => 'Trainee Claimed',
            self::TRAINEE_UNCLAIMED => 'Trainee Unclaimed',
            self::TRAINEE_ASSIGNED => 'Trainee Assigned',
            self::TRAINEE_REMOVED => 'Trainee Removed',
            self::TRAINEE_REACTIVATED => 'Trainee Reactivated',
            self::TRAINEE_ADDED_TO_COURSE => 'Trainee Added to Course',

            self::MENTOR_ADDED => 'Mentor Added',
            self::MENTOR_REMOVED => 'Mentor Removed',

            self::REMARKS_UPDATED => 'Remarks Updated',

            self::FAMILIARISATION_ADDED => 'Familiarisation Added',
            self::FAMILIARISATION_CREATED => 'Familiarisation Created',
            self::FAMILIARISATION_UPDATED => 'Familiarisation Updated',
            self::FAMILIARISATION_DELETED => 'Familiarisation Deleted',

            self::TRAININGLOG_ADDED => 'Training Log Added',
            self::TRAININGLOG_CREATED => 'Training Log Created',
            self::TRAININGLOG_UPDATED => 'Training Log Updated',
            self::TRAININGLOG_REMOVED => 'Training Log Removed',
            self::TRAININGLOG_DELETED => 'Training Log Deleted',

            self::COURSE_CREATED => 'Course Created',
            self::COURSE_UPDATED => 'Course Updated',
            self::COURSE_DELETED => 'Course Deleted',

            self::COT_CREATED => 'Chief Of Training Created',
            self::COT_UPDATED => 'Chief Of Training Updated',
            self::COT_DELETED => 'Chief Of Training Deleted',

            self::CPT_CREATED => 'CPT Created',
            self::CPT_EXAMINER_JOINED => 'CPT Examiner Joined',
            self::CPT_EXAMINER_LEFT => 'CPT Examiner Left',
            self::CPT_LOCAL_JOINED => 'Local Mentor Joined CPT',
            self::CPT_LOCAL_LEFT => 'Local Mentor Left CPT',
            self::CPT_LOG_UPLOADED => 'CPT Log Uploaded',
            self::CPT_PASSED => 'CPT Passed',
            self::CPT_FAILED => 'CPT Failed',
            self::CPT_DELETED => 'CPT Deleted',
            self::CPT_UPDATED => 'CPT Updated',

            self::ROSTER_REMOVED => 'Removed from Roster',
            self::ROSTER_NOTIFIED => 'Notified Roster Removal',

            self::GDPR_DELETION => 'GDPR User Deletion',
            self::API_USER_RETRIEVAL => 'API User Data Retrieval',

            self::USER_ROLES_UPDATED => 'User Roles Updated',
            self::USER_PERMISSIONS_UPDATED => 'User Permissions Updated',
            self::USER_COURSE_ENROLLMENT_ADDED => 'Course Enrollment Added',
            self::USER_COURSE_ENROLLMENT_REMOVED => 'Course Enrollment Removed',
            self::USER_COURSE_ENROLLMENT_UPDATED => 'Course Enrollment Updated',

            self::ROLE_PERMISSIONS_UPDATED => 'Role Permissions Updated',

            self::API_KEY_CREATED => 'API Key Created',
            self::API_KEY_UPDATED => 'API Key Updated',
            self::API_KEY_DELETED => 'API Key Deleted',

            self::EXAMINER_CREATED => 'Examiner Created',
            self::EXAMINER_UPDATED => 'Examiner Updated',
            self::EXAMINER_DELETED => 'Examiner Deleted',

            self::FAMILIARISATION_SECTOR_CREATED => 'Familiarisation Sector Created',
            self::FAMILIARISATION_SECTOR_UPDATED => 'Familiarisation Sector Updated',
            self::FAMILIARISATION_SECTOR_DELETED => 'Familiarisation Sector Deleted',

            self::LEADING_MENTOR_CREATED => 'Leading Mentor Added',
            self::LEADING_MENTOR_DELETED => 'Leading Mentor Removed',

            self::ROLE_CREATED => 'Role Created',
            self::ROLE_UPDATED => 'Role Updated',
            self::ROLE_DELETED => 'Role Deleted',

            self::TIER2_ENDORSEMENT_CREATED => 'Tier 2 Endorsement Created',
            self::TIER2_ENDORSEMENT_UPDATED => 'Tier 2 Endorsement Updated',
            self::TIER2_ENDORSEMENT_DELETED => 'Tier 2 Endorsement Deleted',

            self::ENDORSEMENT_ACTIVITY_UPDATED => 'Endorsement Activity Updated',

            self::WAITING_LIST_ENTRY_UPDATED => 'Waiting List Entry Updated',

            self::WAITING_LIST_RESTRICTION_CREATED => 'Waiting List Restriction Added',
            self::WAITING_LIST_RESTRICTION_UPDATED => 'Waiting List Restriction Updated',
            self::WAITING_LIST_RESTRICTION_DELETED => 'Waiting List Restriction Removed',

            self::USER_CREATED => 'User Created',
            self::USER_UPDATED => 'User Updated',
            self::USER_DELETED => 'User Deleted',

            self::CREATED => 'Created',
            self::UPDATED => 'Updated',
            self::DELETED => 'Deleted',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::WAITING_LIST_JOINED,
            self::TRAINING_STARTED,
            self::ENDORSEMENT_TIER1_GRANTED,
            self::ENDORSEMENT_TIER2_GRANTED,
            self::SOLO_GRANTED,
            self::TRAINEE_CLAIMED,
            self::TRAINEE_ADDED_TO_COURSE,
            self::TRAINEE_REACTIVATED,
            self::MENTOR_ADDED,
            self::FAMILIARISATION_ADDED,
            self::FAMILIARISATION_CREATED,
            self::TRAININGLOG_ADDED,
            self::TRAININGLOG_CREATED,
            self::CORE_TEST_ASSIGNED,
            self::CPT_CREATED,
            self::CPT_PASSED,
            self::COURSE_CREATED,
            self::COT_CREATED,
            self::API_KEY_CREATED,
            self::EXAMINER_CREATED,
            self::FAMILIARISATION_SECTOR_CREATED,
            self::LEADING_MENTOR_CREATED,
            self::ROLE_CREATED,
            self::TIER2_ENDORSEMENT_CREATED,
            self::WAITING_LIST_RESTRICTION_CREATED,
            self::USER_CREATED,
            self::USER_COURSE_ENROLLMENT_ADDED,
            self::CREATED => 'success',

            self::WAITING_LIST_LEFT,
            self::ENDORSEMENT_REMOVED,
            self::ENDORSEMENT_DELETED,
            self::SOLO_REMOVED,
            self::TRAINEE_REMOVED,
            self::MENTOR_REMOVED,
            self::CPT_FAILED,
            self::CPT_DELETED,
            self::ROSTER_REMOVED,
            self::GDPR_DELETION,
            self::COURSE_DELETED,
            self::COT_DELETED,
            self::TRAININGLOG_REMOVED,
            self::TRAININGLOG_DELETED,
            self::FAMILIARISATION_DELETED,
            self::API_KEY_DELETED,
            self::EXAMINER_DELETED,
            self::FAMILIARISATION_SECTOR_DELETED,
            self::LEADING_MENTOR_DELETED,
            self::ROLE_DELETED,
            self::TIER2_ENDORSEMENT_DELETED,
            self::WAITING_LIST_RESTRICTION_DELETED,
            self::USER_DELETED,
            self::USER_COURSE_ENROLLMENT_REMOVED,
            self::DELETED => 'danger',

            self::SOLO_EXTENDED,
            self::TRAINEE_ASSIGNED,
            self::TRAINEE_UNCLAIMED,
            self::REMARKS_UPDATED,
            self::COURSE_FINISHED,
            self::COURSE_UPDATED,
            self::COT_UPDATED,
            self::ROSTER_NOTIFIED,
            self::FAMILIARISATION_UPDATED,
            self::API_KEY_UPDATED,
            self::EXAMINER_UPDATED,
            self::FAMILIARISATION_SECTOR_UPDATED,
            self::ROLE_UPDATED,
            self::ROLE_PERMISSIONS_UPDATED,
            self::TIER2_ENDORSEMENT_UPDATED,
            self::ENDORSEMENT_ACTIVITY_UPDATED,
            self::WAITING_LIST_ENTRY_UPDATED,
            self::WAITING_LIST_RESTRICTION_UPDATED,
            self::USER_UPDATED,
            self::USER_ROLES_UPDATED,
            self::USER_PERMISSIONS_UPDATED,
            self::USER_COURSE_ENROLLMENT_UPDATED,
            self::UPDATED => 'warning',

            self::CPT_EXAMINER_JOINED,
            self::CPT_EXAMINER_LEFT,
            self::CPT_LOCAL_JOINED,
            self::CPT_LOCAL_LEFT,
            self::CPT_LOG_UPLOADED,
            self::CPT_UPDATED => 'info',

            default => 'info',
        };
    }

    public static function fromString(string $action): ?self
    {
        return self::tryFrom($action);
    }

    public static function getLabels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $action) => [$action->value => $action->getLabel()])
            ->toArray();
    }

    public static function getFilterOptions(): array
    {
        return [
            'waiting_list' => 'Waiting List',
            'training' => 'Training',
            'endorsement' => 'Endorsement',
            'solo' => 'Solo',
            'trainee' => 'Trainee',
            'mentor' => 'Mentor',
            'course' => 'Course',
            'remarks' => 'Remarks',
            'familiarisation' => 'Familiarisation',
            'cpt' => 'CPT',
            'apikey' => 'API Key',
            'examiner' => 'Examiner',
            'role' => 'Role',
            'tier2endorsement' => 'Tier 2 Endorsement',
            'endorsementactivity' => 'Endorsement Activity',
            'waitinglistrestriction' => 'Waiting List Restriction',
            'user' => 'User',
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
        ];
    }
}
