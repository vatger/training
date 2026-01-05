<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $model_type
 * @property int|null $model_id
 * @property array<array-key, mixed>|null $properties
 * @property string|null $description
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $subject
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog byAction(string $action)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog forModel(string $modelType, int $modelId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog recent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereUserId($value)
 */
	class ActivityLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $key
 * @property array<array-key, mixed> $permissions
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property string|null $last_used_ip
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $masked_key
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereLastUsedIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiKey whereUpdatedAt($value)
 */
	class ApiKey extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityLog> $activityLogs
 * @property-read int|null $activity_logs_count
 * @property-read \App\Models\Course $course
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChiefOfTraining newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChiefOfTraining newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChiefOfTraining query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChiefOfTraining whereCourseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChiefOfTraining whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChiefOfTraining whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChiefOfTraining whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChiefOfTraining whereUserId($value)
 */
	class ChiefOfTraining extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $trainee_display_name
 * @property string|null $description
 * @property string $airport_name
 * @property string $airport_icao
 * @property string|null $solo_station
 * @property int|null $mentor_group_id
 * @property int $min_rating
 * @property int $max_rating
 * @property string $type
 * @property string $position
 * @property array<array-key, mixed> $moodle_course_ids
 * @property int|null $familiarisation_sector_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $activeTrainees
 * @property-read int|null $active_trainees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityLog> $activityLogs
 * @property-read int|null $activity_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $allTrainees
 * @property-read int|null $all_trainees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $completedTrainees
 * @property-read int|null $completed_trainees_count
 * @property-read \App\Models\FamiliarisationSector|null $familiarisationSector
 * @property-read string $position_display
 * @property-read string $type_display
 * @property-read \App\Models\Role|null $mentorGroup
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $mentors
 * @property-read int|null $mentors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WaitingListEntry> $waitingListEntries
 * @property-read int|null $waiting_list_entries_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course availableFor(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course byType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course forRating(int $rating)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereAirportIcao($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereAirportName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereFamiliarisationSectorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereMaxRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereMentorGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereMinRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereMoodleCourseIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereSoloStation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereTraineeDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Course whereUpdatedAt($value)
 */
	class Course extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $trainee_id
 * @property int|null $examiner_id
 * @property int|null $local_id
 * @property int|null $course_id
 * @property \Illuminate\Support\Carbon $date
 * @property bool|null $passed
 * @property bool $confirmed
 * @property bool $log_uploaded
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Course|null $course
 * @property-read \App\Models\User|null $examiner
 * @property-read \App\Models\User|null $local
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CptLog> $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\User $trainee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt confirmed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt forCourse(int $courseId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt whereConfirmed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt whereCourseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt whereExaminerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt whereLocalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt whereLogUploaded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt wherePassed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt whereTraineeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cpt whereUpdatedAt($value)
 */
	class Cpt extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cpt_id
 * @property int $uploaded_by_id
 * @property string $log_file
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Cpt $cpt
 * @property-read string $file_name
 * @property-read string $file_url
 * @property-read \App\Models\User $uploadedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CptLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CptLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CptLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CptLog whereCptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CptLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CptLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CptLog whereLogFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CptLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CptLog whereUploadedById($value)
 */
	class CptLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $endorsement_id
 * @property int $vatsim_id
 * @property string $position
 * @property float $activity_minutes
 * @property \Illuminate\Support\Carbon|null $last_activity_date
 * @property \Illuminate\Support\Carbon $last_updated
 * @property \Illuminate\Support\Carbon|null $removal_date
 * @property bool $removal_notified
 * @property \Illuminate\Support\Carbon|null $created_at_vateud
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float $activity_hours
 * @property-read float $progress
 * @property-read string $status
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity markedForRemoval()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity needsUpdate()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity whereActivityMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity whereCreatedAtVateud($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity whereEndorsementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity whereLastActivityDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity whereLastUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity whereRemovalDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity whereRemovalNotified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EndorsementActivity whereVatsimId($value)
 */
	class EndorsementActivity extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $callsign
 * @property array<array-key, mixed> $positions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $full_display
 * @property-read string $positions_display
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Examiner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Examiner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Examiner query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Examiner whereCallsign($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Examiner whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Examiner whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Examiner wherePositions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Examiner whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Examiner whereUserId($value)
 */
	class Examiner extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $familiarisation_sector_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FamiliarisationSector $sector
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Familiarisation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Familiarisation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Familiarisation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Familiarisation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Familiarisation whereFamiliarisationSectorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Familiarisation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Familiarisation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Familiarisation whereUserId($value)
 */
	class Familiarisation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $fir
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Course> $courses
 * @property-read int|null $courses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Familiarisation> $familiarisations
 * @property-read int|null $familiarisations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamiliarisationSector newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamiliarisationSector newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamiliarisationSector query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamiliarisationSector whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamiliarisationSector whereFir($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamiliarisationSector whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamiliarisationSector whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamiliarisationSector whereUpdatedAt($value)
 */
	class FamiliarisationSector extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $fir
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeadingMentor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeadingMentor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeadingMentor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeadingMentor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeadingMentor whereFir($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeadingMentor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeadingMentor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeadingMentor whereUserId($value)
 */
	class LeadingMentor extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $group
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 */
	class Permission extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role leadershipRoles()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role mentorRoles()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $last_session
 * @property \Illuminate\Support\Carbon|null $removal_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RosterEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RosterEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RosterEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RosterEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RosterEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RosterEntry whereLastSession($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RosterEntry whereRemovalDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RosterEntry whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RosterEntry whereUserId($value)
 */
	class RosterEntry extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $position
 * @property int $moodle_course_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tier2Endorsement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tier2Endorsement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tier2Endorsement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tier2Endorsement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tier2Endorsement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tier2Endorsement whereMoodleCourseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tier2Endorsement whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tier2Endorsement wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tier2Endorsement whereUpdatedAt($value)
 */
	class Tier2Endorsement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $trainee_id
 * @property int|null $mentor_id
 * @property int|null $course_id
 * @property \Illuminate\Support\Carbon $session_date
 * @property string $position
 * @property string $type
 * @property string|null $traffic_level
 * @property string|null $traffic_complexity
 * @property string|null $runway_configuration
 * @property string|null $surrounding_stations
 * @property int|null $session_duration
 * @property string|null $special_procedures
 * @property string|null $airspace_restrictions
 * @property int $theory
 * @property string|null $theory_positives
 * @property string|null $theory_negatives
 * @property int $phraseology
 * @property string|null $phraseology_positives
 * @property string|null $phraseology_negatives
 * @property int $coordination
 * @property string|null $coordination_positives
 * @property string|null $coordination_negatives
 * @property int $tag_management
 * @property string|null $tag_management_positives
 * @property string|null $tag_management_negatives
 * @property int $situational_awareness
 * @property string|null $situational_awareness_positives
 * @property string|null $situational_awareness_negatives
 * @property int $problem_recognition
 * @property string|null $problem_recognition_positives
 * @property string|null $problem_recognition_negatives
 * @property int $traffic_planning
 * @property string|null $traffic_planning_positives
 * @property string|null $traffic_planning_negatives
 * @property int $reaction
 * @property string|null $reaction_positives
 * @property string|null $reaction_negatives
 * @property int $separation
 * @property string|null $separation_positives
 * @property string|null $separation_negatives
 * @property int $efficiency
 * @property string|null $efficiency_positives
 * @property string|null $efficiency_negatives
 * @property int $ability_to_work_under_pressure
 * @property string|null $ability_to_work_under_pressure_positives
 * @property string|null $ability_to_work_under_pressure_negatives
 * @property int $motivation
 * @property string|null $motivation_positives
 * @property string|null $motivation_negatives
 * @property string|null $internal_remarks
 * @property string|null $final_comment
 * @property bool $result
 * @property string|null $next_step
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Course|null $course
 * @property-read string $ability_to_work_under_pressure_display
 * @property-read float $average_rating
 * @property-read string $coordination_display
 * @property-read string $efficiency_display
 * @property-read string $motivation_display
 * @property-read string $phraseology_display
 * @property-read string $problem_recognition_display
 * @property-read string $reaction_display
 * @property-read string $separation_display
 * @property-read string $situational_awareness_display
 * @property-read string $tag_management_display
 * @property-read string $theory_display
 * @property-read string|null $traffic_complexity_display
 * @property-read string|null $traffic_level_display
 * @property-read string $traffic_planning_display
 * @property-read string $type_display
 * @property-read \App\Models\User|null $mentor
 * @property-read \App\Models\User $trainee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog byMentor(int $mentorId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog forCourse(int $courseId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog forTrainee(int $traineeId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog passed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog recent()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereAbilityToWorkUnderPressure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereAbilityToWorkUnderPressureNegatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereAbilityToWorkUnderPressurePositives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereAirspaceRestrictions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereCoordination($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereCoordinationNegatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereCoordinationPositives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereCourseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereEfficiency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereEfficiencyNegatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereEfficiencyPositives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereFinalComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereInternalRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereMentorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereMotivation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereMotivationNegatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereMotivationPositives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereNextStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog wherePhraseology($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog wherePhraseologyNegatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog wherePhraseologyPositives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereProblemRecognition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereProblemRecognitionNegatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereProblemRecognitionPositives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereReaction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereReactionNegatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereReactionPositives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereRunwayConfiguration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereSeparation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereSeparationNegatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereSeparationPositives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereSessionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereSessionDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereSituationalAwareness($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereSituationalAwarenessNegatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereSituationalAwarenessPositives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereSpecialProcedures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereSurroundingStations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereTagManagement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereTagManagementNegatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereTagManagementPositives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereTheory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereTheoryNegatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereTheoryPositives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereTrafficComplexity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereTrafficLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereTrafficPlanning($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereTrafficPlanningNegatives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereTrafficPlanningPositives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereTraineeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingLog whereUpdatedAt($value)
 */
	class TrainingLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $vatsim_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $subdivision
 * @property int $rating
 * @property int|null $last_known_rating
 * @property \Illuminate\Support\Carbon|null $last_rating_change
 * @property string|null $rating_upgraded_at
 * @property int $solo_days_used
 * @property bool $is_staff
 * @property bool $is_superuser
 * @property bool $is_admin
 * @property string|null $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Course> $activeCourses
 * @property-read int|null $active_courses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Course> $chiefOfTrainingCourses
 * @property-read int|null $chief_of_training_courses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cpt> $cpts
 * @property-read int|null $cpts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EndorsementActivity> $endorsementActivities
 * @property-read int|null $endorsement_activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cpt> $examinedCpts
 * @property-read int|null $examined_cpts_count
 * @property-read \App\Models\Examiner|null $examiner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Familiarisation> $familiarisations
 * @property-read int|null $familiarisations_count
 * @property-read string $full_name
 * @property-read string $name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeadingMentor> $leadingMentorFirs
 * @property-read int|null $leading_mentor_firs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cpt> $localCpts
 * @property-read int|null $local_cpts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Course> $mentorCourses
 * @property-read int|null $mentor_courses_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TrainingLog> $trainingLogs
 * @property-read int|null $training_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WaitingListEntry> $waitingListEntries
 * @property-read int|null $waiting_list_entries_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User admins()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User leadership()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User mentors()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User vatsimUsers()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsStaff($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsSuperuser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastKnownRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastRatingChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRatingUpgradedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSoloDaysUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSubdivision($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereVatsimId($value)
 */
	class User extends \Eloquent implements \Filament\Models\Contracts\FilamentUser {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $theme
 * @property bool $english_only
 * @property array<array-key, mixed>|null $notification_preferences
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereEnglishOnly($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereNotificationPreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereTheme($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereUserId($value)
 */
	class UserSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property \Illuminate\Support\Carbon $date_added
 * @property float $activity
 * @property \Illuminate\Support\Carbon $hours_updated
 * @property string|null $remarks
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Course $course
 * @property-read int $position_in_queue
 * @property-read string $waiting_time
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WaitingListEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WaitingListEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WaitingListEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WaitingListEntry whereActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WaitingListEntry whereCourseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WaitingListEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WaitingListEntry whereDateAdded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WaitingListEntry whereHoursUpdated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WaitingListEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WaitingListEntry whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WaitingListEntry whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WaitingListEntry whereUserId($value)
 */
	class WaitingListEntry extends \Eloquent {}
}

