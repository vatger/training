<?php

namespace App\Http\Controllers\Training;

use App\Domain\Training\Actions\StartTraining;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class WaitingListController extends Controller
{
    public function __construct(
        private StartTraining $startTraining,
    ) {}

    public function mentorView(Request $request): Response
    {
        if (! Gate::allows('mentor')) {
            abort(403, 'Access denied. Mentor privileges required.');
        }

        $user = $request->user();

        $query = Course::query()
            ->select([
                'courses.id',
                'courses.name',
                'courses.type',
                'courses.position',
                DB::raw('COUNT(waiting_list_entries.id) as waiting_count'),
            ])
            ->leftJoin('waiting_list_entries', 'courses.id', '=', 'waiting_list_entries.course_id')
            ->groupBy('courses.id', 'courses.name', 'courses.type', 'courses.position');

        if (! $user->is_superuser && ! $user->is_admin) {
            $lmFirs = $user->getLeadingMentorFirs();

            if (! empty($lmFirs)) {
                $query->where(function ($q) use ($user, $lmFirs) {
                    $q->whereExists(function ($subQuery) use ($user) {
                        $subQuery->select(DB::raw(1))
                            ->from('course_mentors')
                            ->whereRaw('course_mentors.course_id = courses.id')
                            ->where('course_mentors.user_id', $user->id);
                    })->orWhereExists(function ($subQuery) use ($lmFirs) {
                        $subQuery->select(DB::raw(1))
                            ->from('roles')
                            ->whereRaw('courses.mentor_group_id = roles.id')
                            ->where(function ($firQuery) use ($lmFirs) {
                                foreach ($lmFirs as $fir) {
                                    $firQuery->orWhere('roles.name', 'LIKE', "%{$fir}%");
                                }
                            });
                    });
                });
            } else {
                $query->join('course_mentors', 'courses.id', '=', 'course_mentors.course_id')
                    ->where('course_mentors.user_id', $user->id);
            }
        }

        $courses = $query->get();

        $statistics = [
            'total_waiting' => 0,
            'rtg_waiting' => 0,
            'edmt_waiting' => 0,
            'fam_waiting' => 0,
            'gst_waiting' => 0,
            'rst_waiting' => 0,
        ];

        $courseIds = $courses->pluck('id');

        $waitingEntries = WaitingListEntry::whereIn('course_id', $courseIds)
            ->with(['user:id,vatsim_id,first_name,last_name'])
            ->select(['id', 'user_id', 'course_id', 'activity', 'remarks', 'date_added'])
            ->orderBy('course_id')
            ->orderBy('date_added')
            ->get()
            ->groupBy('course_id');

        $courseData = $courses->map(function ($course) use ($waitingEntries, &$statistics) {
            $entries = $waitingEntries->get($course->id, collect());

            $formattedEntries = $entries->map(fn ($entry) => [
                'id' => $entry->id,
                'name' => $entry->user->name,
                'vatsim_id' => $entry->user->vatsim_id,
                'activity' => round($entry->activity, 2),
                'waiting_time' => $entry->waiting_time,
                'waiting_days' => $entry->date_added->diffInDays(now()),
                'remarks' => $entry->remarks,
                'date_added' => $entry->date_added->format('Y-m-d H:i:s'),
            ]);

            $waitingCount = $entries->count();
            $statistics['total_waiting'] += $waitingCount;
            $statistics[strtolower($course->type).'_waiting'] += $waitingCount;

            return [
                'id' => $course->id,
                'name' => $course->name,
                'type' => $course->type,
                'type_display' => $this->getTypeDisplay($course->type),
                'position' => $course->position,
                'position_display' => $this->getPositionDisplay($course->position),
                'waiting_count' => $waitingCount,
                'waiting_list' => $formattedEntries->values(),
            ];
        });

        $sortedCourseData = $courseData->sortBy(function ($course) {
            $typeOrder = ['RTG' => 1, 'EDMT' => 2, 'FAM' => 3, 'GST' => 4, 'RST' => 5];
            $posOrder = ['GND' => 1, 'TWR' => 2, 'APP' => 3, 'CTR' => 4];

            return [$typeOrder[$course['type']] ?? 99, $posOrder[$course['position']] ?? 99];
        })->values();

        return Inertia::render('training/mentor-waiting-lists', [
            'courses' => $sortedCourseData,
            'statistics' => $statistics,
            'config' => [
                'min_activity' => config('services.training.min_activity', 10),
                'display_activity' => config('services.training.display_activity', 8),
            ],
        ]);
    }

    public function startTraining(Request $request, WaitingListEntry $entry)
    {
        if (! Gate::allows('mentor')) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $user = $request->user();

        if (! $this->userCanMentorEntry($user, $entry)) {
            return response()->json(['error' => 'You cannot mentor this course'], 403);
        }

        try {
            [$success, $message] = $this->startTraining->execute($entry, $user);

            return back()->with('flash', ['success' => $success, 'message' => $message]);
        } catch (\Exception $e) {
            Log::error('Error starting training', ['entry_id' => $entry->id, 'mentor_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'An error occurred while starting training.'], 500);
        }
    }

    public function updateRemarks(Request $request)
    {
        if (! Gate::allows('mentor')) {
            return back()->withErrors(['error' => 'Access denied']);
        }

        $validated = $request->validate([
            'entry_id' => 'required|integer|exists:waiting_list_entries,id',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $entry = WaitingListEntry::findOrFail($validated['entry_id']);
        $user = $request->user();

        if (! $this->userCanMentorEntry($user, $entry)) {
            return back()->withErrors(['error' => 'You cannot modify this entry']);
        }

        try {
            $entry->update(['remarks' => $validated['remarks'] ?? '']);

            return back();
        } catch (\Exception $e) {
            Log::error('Error updating remarks', ['entry_id' => $entry->id, 'mentor_id' => $user->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'An error occurred while updating remarks.']);
        }
    }

    private function userCanMentorEntry(User $user, WaitingListEntry $entry): bool
    {
        if ($user->is_superuser || $user->is_admin) {
            return true;
        }

        if (DB::table('course_mentors')->where('course_id', $entry->course_id)->where('user_id', $user->id)->exists()) {
            return true;
        }

        $course = Course::find($entry->course_id);
        if (! $course?->mentor_group_id) {
            return false;
        }

        $mentorGroupName = DB::table('roles')->where('id', $course->mentor_group_id)->value('name');
        if (! $mentorGroupName) {
            return false;
        }

        $fir = $user->getFirFromMentorGroup($mentorGroupName);

        return $fir && $user->isLeadingMentorForFir($fir);
    }

    private function getTypeDisplay(string $type): string
    {
        return match ($type) {
            'RTG' => 'Rating',
            'EDMT' => 'Endorsement',
            'FAM' => 'Familiarisation',
            'GST' => 'Guest',
            'RST' => 'Roster',
            default => $type,
        };
    }

    private function getPositionDisplay(string $position): string
    {
        return match ($position) {
            'GND' => 'Ground',
            'TWR' => 'Tower',
            'APP' => 'Approach',
            'CTR' => 'Center',
            default => $position,
        };
    }
}
