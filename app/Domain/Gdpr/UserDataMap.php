<?php

namespace App\Domain\Gdpr;

use App\Models\Cpt;
use App\Models\CptLog;
use App\Models\Examiner;
use App\Models\Familiarisation;
use App\Models\SubfamiliarisationSession;
use App\Models\TrainingLog;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Models\WaitingListRestriction;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for every place a user is referenced.
 *
 * - "owned" sources are rows that belong to the user and are deleted with them.
 * - "authored" sources are rows that belong to somebody else (a trainee's training
 *   log, a CPT, ...) but point at the user as mentor / examiner / author. They are
 *   kept and re-pointed at the "GDPR Removed" placeholder user.
 * - "review tables" are the ones holding free text that may still contain the
 *   user's identity, and therefore need a manual look before a request is closed.
 *
 * The export endpoint, the admin review page and the deletion all read from here,
 * and a test fails when a foreign key to users is not listed, so a new table cannot
 * silently escape a GDPR request.
 */
class UserDataMap
{
    private const TRAINING_LOG_CATEGORIES = [
        'theory', 'phraseology', 'coordination', 'tag_management', 'situational_awareness',
        'problem_recognition', 'traffic_planning', 'reaction', 'separation', 'efficiency',
        'ability_to_work_under_pressure', 'motivation',
    ];

    /**
     * Rows that are deleted together with the user. Order matters: children first.
     *
     * @return array<string, array{label: string, table: string, user_columns: array<int, string>, query: \Closure, columns: array<int, string>, resolve?: array<string, string>, exclude?: array<int, string>, model?: class-string}>
     */
    public static function owned(): array
    {
        return [
            'cpt_logs' => [
                'label' => 'CPT logs on own CPTs',
                'table' => 'cpt_logs',
                'user_columns' => [],
                'query' => fn (User $u) => DB::table('cpt_logs')
                    ->whereIn('cpt_id', DB::table('cpts')->where('trainee_id', $u->id)->select('id')),
                'columns' => ['id', 'cpt_id', 'log_file', 'created_at'],
            ],
            'cpts' => [
                'label' => 'CPTs (as trainee)',
                'table' => 'cpts',
                'user_columns' => ['trainee_id'],
                'query' => fn (User $u) => DB::table('cpts')->where('trainee_id', $u->id),
                'columns' => ['id', 'date', 'passed', 'confirmed', 'examiner_id', 'local_id', 'course_id'],
                'resolve' => ['examiner_id' => 'users', 'local_id' => 'users', 'course_id' => 'courses'],
                'model' => Cpt::class,
            ],
            'training_logs' => [
                'label' => 'Training logs (as trainee)',
                'table' => 'training_logs',
                'user_columns' => ['trainee_id'],
                'query' => fn (User $u) => DB::table('training_logs')->where('trainee_id', $u->id),
                'columns' => ['id', 'session_date', 'position', 'type', 'result', 'mentor_id', 'course_id'],
                'resolve' => ['mentor_id' => 'users', 'course_id' => 'courses'],
                'model' => TrainingLog::class,
            ],
            'course_trainees' => [
                'label' => 'Course enrolments (as trainee)',
                'table' => 'course_trainees',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('course_trainees')->where('user_id', $u->id),
                'columns' => ['id', 'course_id', 'status', 'claimed_by_mentor_id', 'claimed_at', 'completed_at', 'remarks', 'remark_author_id'],
                'resolve' => ['course_id' => 'courses', 'claimed_by_mentor_id' => 'users', 'remark_author_id' => 'users'],
            ],
            'subfamiliarisation_sessions' => [
                'label' => 'Subfamiliarisation sessions (as trainee)',
                'table' => 'subfamiliarisation_sessions',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('subfamiliarisation_sessions')->where('user_id', $u->id),
                'columns' => ['id', 'subfamiliarisation_id', 'mentor_id', 'completed_at', 'remarks'],
                'resolve' => ['subfamiliarisation_id' => 'subfamiliarisations', 'mentor_id' => 'users'],
                'model' => SubfamiliarisationSession::class,
            ],
            'waiting_list_entries' => [
                'label' => 'Waiting lists',
                'table' => 'waiting_list_entries',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('waiting_list_entries')->where('user_id', $u->id),
                'columns' => ['id', 'course_id', 'date_added', 'activity', 'remarks'],
                'resolve' => ['course_id' => 'courses'],
                'model' => WaitingListEntry::class,
            ],
            'waiting_list_restrictions' => [
                'label' => 'Waiting list restrictions',
                'table' => 'waiting_list_restrictions',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('waiting_list_restrictions')->where('user_id', $u->id),
                'columns' => ['id', 'type', 'expires_at'],
                'model' => WaitingListRestriction::class,
            ],
            'familiarisations' => [
                'label' => 'Familiarisations',
                'table' => 'familiarisations',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('familiarisations')->where('user_id', $u->id),
                'columns' => ['id', 'familiarisation_sector_id', 'created_at'],
                'resolve' => ['familiarisation_sector_id' => 'familiarisation_sectors'],
                'model' => Familiarisation::class,
            ],
            'course_mentors' => [
                'label' => 'Mentor assignments',
                'table' => 'course_mentors',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('course_mentors')->where('user_id', $u->id),
                'columns' => ['id', 'course_id'],
                'resolve' => ['course_id' => 'courses'],
            ],
            'chief_of_trainings' => [
                'label' => 'Chief of Training assignments',
                'table' => 'chief_of_trainings',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('chief_of_trainings')->where('user_id', $u->id),
                'columns' => ['id', 'course_id'],
                'resolve' => ['course_id' => 'courses'],
            ],
            'leading_mentors' => [
                'label' => 'Leading mentor assignments',
                'table' => 'leading_mentors',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('leading_mentors')->where('user_id', $u->id),
                'columns' => ['id', 'fir'],
            ],
            'examiners' => [
                'label' => 'Examiner profile',
                'table' => 'examiners',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('examiners')->where('user_id', $u->id),
                'columns' => ['id', 'callsign', 'positions'],
                'model' => Examiner::class,
            ],
            'user_settings' => [
                'label' => 'Settings',
                'table' => 'user_settings',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('user_settings')->where('user_id', $u->id),
                'columns' => ['id', 'theme', 'english_only', 'notification_preferences'],
            ],
            'user_roles' => [
                'label' => 'Roles',
                'table' => 'user_roles',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('user_roles')->where('user_id', $u->id),
                'columns' => ['id', 'role_id'],
                'resolve' => ['role_id' => 'roles'],
            ],
            'user_permissions' => [
                'label' => 'Direct permissions',
                'table' => 'user_permissions',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('user_permissions')->where('user_id', $u->id),
                'columns' => ['id', 'permission_id'],
                'resolve' => ['permission_id' => 'permissions'],
            ],
            // These two are keyed by the VATSIM id instead of the users.id.
            'roster_entries' => [
                'label' => 'Roster entry',
                'table' => 'roster_entries',
                'user_columns' => [],
                'query' => fn (User $u) => self::byVatsimId('roster_entries', 'user_id', $u),
                'columns' => ['id', 'last_session', 'removal_date'],
            ],
            'endorsement_activities' => [
                'label' => 'Endorsement activity',
                'table' => 'endorsement_activities',
                'user_columns' => [],
                'query' => fn (User $u) => self::byVatsimId('endorsement_activities', 'vatsim_id', $u),
                'columns' => ['id', 'position', 'activity_minutes', 'last_updated', 'removal_date'],
            ],
            'sessions' => [
                'label' => 'Login sessions',
                'table' => 'sessions',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('sessions')->where('user_id', $u->id),
                'columns' => ['id', 'ip_address', 'user_agent', 'last_activity'],
                'exclude' => ['payload'],
            ],
            'password_reset_tokens' => [
                'label' => 'Password reset tokens',
                'table' => 'password_reset_tokens',
                'user_columns' => [],
                'query' => fn (User $u) => filled($u->email)
                    ? DB::table('password_reset_tokens')->where('email', $u->email)
                    : DB::table('password_reset_tokens')->whereRaw('1 = 0'),
                'columns' => ['email', 'created_at'],
                'exclude' => ['token'],
            ],
            'activity_logs' => [
                'label' => 'Activity log entries (caused by or about the user)',
                'table' => 'activity_logs',
                'user_columns' => ['user_id'],
                'query' => fn (User $u) => DB::table('activity_logs')
                    ->where('user_id', $u->id)
                    ->orWhere(fn ($q) => $q->where('model_type', User::class)->where('model_id', $u->id)),
                'columns' => ['id', 'action', 'description', 'ip_address', 'created_at'],
            ],
        ];
    }

    /**
     * Rows owned by somebody else that reference the user. They are kept and
     * the referencing column is pointed at the "GDPR Removed" placeholder.
     *
     * @return array<string, array{label: string, table: string, column: string, columns: array<int, string>, resolve?: array<string, string>}>
     */
    public static function authored(): array
    {
        return [
            'training_logs_as_mentor' => [
                'label' => 'Training logs written as mentor',
                'table' => 'training_logs',
                'column' => 'mentor_id',
                'columns' => ['id', 'session_date', 'position', 'trainee_id', 'course_id'],
                'resolve' => ['trainee_id' => 'users', 'course_id' => 'courses'],
            ],
            'cpts_as_examiner' => [
                'label' => 'CPTs examined',
                'table' => 'cpts',
                'column' => 'examiner_id',
                'columns' => ['id', 'date', 'trainee_id', 'course_id'],
                'resolve' => ['trainee_id' => 'users', 'course_id' => 'courses'],
            ],
            'cpts_as_local' => [
                'label' => 'CPTs as local contact',
                'table' => 'cpts',
                'column' => 'local_id',
                'columns' => ['id', 'date', 'trainee_id', 'course_id'],
                'resolve' => ['trainee_id' => 'users', 'course_id' => 'courses'],
            ],
            'cpt_logs_uploaded' => [
                'label' => 'CPT logs uploaded',
                'table' => 'cpt_logs',
                'column' => 'uploaded_by_id',
                'columns' => ['id', 'cpt_id', 'log_file', 'created_at'],
            ],
            'trainee_remarks_written' => [
                'label' => 'Trainee remarks written',
                'table' => 'course_trainees',
                'column' => 'remark_author_id',
                'columns' => ['id', 'user_id', 'course_id', 'remarks'],
                'resolve' => ['user_id' => 'users', 'course_id' => 'courses'],
            ],
            'trainees_claimed' => [
                'label' => 'Trainees claimed',
                'table' => 'course_trainees',
                'column' => 'claimed_by_mentor_id',
                'columns' => ['id', 'user_id', 'course_id', 'claimed_at'],
                'resolve' => ['user_id' => 'users', 'course_id' => 'courses'],
            ],
            'subfamiliarisation_sessions_as_mentor' => [
                'label' => 'Subfamiliarisation sessions mentored',
                'table' => 'subfamiliarisation_sessions',
                'column' => 'mentor_id',
                'columns' => ['id', 'user_id', 'subfamiliarisation_id', 'remarks'],
                'resolve' => ['user_id' => 'users', 'subfamiliarisation_id' => 'subfamiliarisations'],
            ],
        ];
    }

    /**
     * Tables whose free text may still identify the user and is therefore reviewed
     * by hand. "authors" are the columns that made the user the writer of the text.
     *
     * @return array<string, array{fields: array<int, string>, authors: array<int, string>, label: string}>
     */
    public static function reviewTables(): array
    {
        $trainingLogFields = ['runway_configuration', 'surrounding_stations', 'special_procedures', 'airspace_restrictions'];
        foreach (self::TRAINING_LOG_CATEGORIES as $category) {
            $trainingLogFields[] = "{$category}_positives";
            $trainingLogFields[] = "{$category}_negatives";
        }
        array_push($trainingLogFields, 'internal_remarks', 'final_comment', 'next_step');

        return [
            'training_logs' => [
                'label' => 'Training log',
                'fields' => $trainingLogFields,
                'authors' => ['mentor_id'],
            ],
            'course_trainees' => [
                'label' => 'Course enrolment remark',
                'fields' => ['remarks'],
                'authors' => ['remark_author_id'],
            ],
            'subfamiliarisation_sessions' => [
                'label' => 'Subfamiliarisation session',
                'fields' => ['remarks'],
                'authors' => ['mentor_id'],
            ],
            'waiting_list_entries' => [
                'label' => 'Waiting list entry',
                'fields' => ['remarks'],
                'authors' => [],
            ],
        ];
    }

    /**
     * Everything stored about the user, grouped by source.
     *
     * @param  bool  $full  true = every column (export), false = concise columns (admin overview)
     * @return array{profile: array<string, mixed>, sections: array<string, array{kind: string, label: string, rows: array<int, array<string, mixed>>}>}
     */
    public static function collect(User $user, bool $full = false): array
    {
        $sections = [];

        foreach (self::owned() as $key => $source) {
            $sections[$key] = [
                'kind' => 'owned',
                'label' => $source['label'],
                'rows' => self::withInvolvedUser(self::rows($source['query']($user), $source, $full), $user, $full),
            ];
        }

        $reviewTables = self::reviewTables();
        foreach (self::authored() as $key => $source) {
            if ($full && isset($reviewTables[$source['table']])) {
                $source['columns'] = array_merge($source['columns'], $reviewTables[$source['table']]['fields']);
            }

            $sections[$key] = [
                'kind' => 'authored',
                'label' => $source['label'],
                'rows' => self::withInvolvedUser(self::rows(DB::table($source['table'])->where($source['column'], $user->id), $source, $full), $user, $full),
            ];
        }

        return [
            'profile' => $user->only([
                'id', 'vatsim_id', 'first_name', 'last_name', 'email', 'subdivision', 'rating', 'last_rating_change',
                'last_known_rating', 'rating_upgraded_at', 'rating_upgrade_pending', 'solo_days_used',
                'is_staff', 'is_superuser', 'is_admin', 'created_at', 'updated_at',
            ]),
            'sections' => $sections,
        ];
    }

    /**
     * Identifiers a reviewer should look for in free text.
     *
     * @return array<int, string>
     */
    public static function searchTerms(User $user): array
    {
        return array_values(array_filter([
            trim("{$user->first_name} {$user->last_name}"),
            $user->vatsim_id ? (string) $user->vatsim_id : null,
            $user->email,
        ], fn ($term) => filled($term)));
    }

    public static function deleteCptLogFile(string $path): void
    {
        (new CptLog(['log_file' => $path]))->deleteFile();
    }

    /**
     * The overview shows the user's name on every row, so it is obvious why the row is listed.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private static function withInvolvedUser(array $rows, User $user, bool $full): array
    {
        if ($full) {
            return $rows;
        }

        $label = trim("{$user->first_name} {$user->last_name}").($user->vatsim_id ? " ({$user->vatsim_id})" : '');

        return array_map(fn (array $row) => ['involved_user' => $label] + $row, $rows);
    }

    private static function byVatsimId(string $table, string $column, User $user): Builder
    {
        $query = DB::table($table);

        return $user->vatsim_id ? $query->where($column, $user->vatsim_id) : $query->whereRaw('1 = 0');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function rows(Builder $query, array $source, bool $full): array
    {
        $rows = $query->get()->map(fn ($row) => (array) $row);

        if ($full) {
            $exclude = array_flip($source['exclude'] ?? []);

            return $rows->map(fn (array $row) => array_diff_key($row, $exclude))->all();
        }

        $rows = $rows->map(fn (array $row) => array_intersect_key($row, array_flip($source['columns'])));

        return self::resolveNames($rows->all(), $source['resolve'] ?? []);
    }

    /**
     * Replaces id columns with a readable name (course_id => course) for the overview.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $resolve
     * @return array<int, array<string, mixed>>
     */
    private static function resolveNames(array $rows, array $resolve): array
    {
        foreach ($resolve as $column => $table) {
            $ids = collect($rows)->pluck($column)->filter()->unique()->values()->all();
            if ($ids === []) {
                continue;
            }

            $names = $table === 'users'
                ? DB::table('users')->whereIn('id', $ids)->get()
                    ->mapWithKeys(fn ($u) => [$u->id => trim("{$u->first_name} {$u->last_name}").($u->vatsim_id ? " ({$u->vatsim_id})" : '')])
                : DB::table($table)->whereIn('id', $ids)->pluck('name', 'id');

            $label = preg_replace('/_id$/', '', $column);

            foreach ($rows as &$row) {
                $row = collect($row)->mapWithKeys(fn ($value, $key) => $key === $column
                    ? [$label => $value === null ? null : ($names[$value] ?? "#{$value}")]
                    : [$key => $value])->all();
            }
            unset($row);
        }

        return $rows;
    }
}
