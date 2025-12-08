<?php

namespace App\Http\Controllers;

use App\Models\Cpt;
use App\Models\CptLog;
use App\Models\Course;
use App\Models\Examiner;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\VatgerService;
use App\Services\VatEudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class CptController extends Controller
{
    protected $vatgerApi;
    protected $vatEudService;

    public function __construct(VatgerService $vatgerApi, VatEudService $vatEudService)
    {
        $this->vatgerApi = $vatgerApi;
        $this->vatEudService = $vatEudService;
    }

    private function sendConfirmedCpts()
    {
        return; // TODO: Remove with release
        $cpts = Cpt::with(['trainee', 'course'])
            ->where('confirmed', true)
            ->whereNull('passed')
            ->orderBy('date')
            ->get();

        if ($cpts->isEmpty()) {
            return;
        }

        $cptData = $cpts->map(function ($cpt) {
            return [
                'trainee' => $cpt->trainee->full_name,
                'date' => $cpt->date->format('d.m.y H:i') . 'lcl',
                'position' => $cpt->course->solo_station,
            ];
        })->toArray();

        $this->vatgerApi->postConfirmedCpts($cptData);
    }

    private function notifyAvailableCpt(Cpt $cpt)
    {

        $notifyUsers = collect();

        if (!$cpt->examiner_id) {
            $possibleExaminers = Examiner::with('user')
                ->whereJsonContains('positions', $cpt->course->position)
                ->get();

            foreach ($possibleExaminers as $examiner) {
                $notifyUsers->push($examiner->user);
            }
        }

        if (!$cpt->local_id) {
            $cpt->course->load('mentors');
            foreach ($cpt->course->mentors as $mentor) {
                $notifyUsers->push($mentor);
            }
        }

        $notifyUsers = $notifyUsers->unique('id');

        foreach ($notifyUsers as $user) {
            if ($user->vatsim_id) {
                $this->vatgerApi->sendNotification(
                    $user->vatsim_id,
                    'Available CPT',
                    "A new CPT is available: {$cpt->course->solo_station} on {$cpt->date->format('d.m.Y at H:i')}lcl.",
                    'Training Centre',
                    route('cpt.index')
                );
            }
        }
    }

    private function notifyUnassignment(Cpt $cpt, string $role, User $unassignedUser)
    {
        $cpt->load(['trainee', 'course', 'examiner', 'local']);

        $notifyUsers = collect();

        if ($cpt->examiner_id && $cpt->examiner_id !== $unassignedUser->id) {
            $notifyUsers->push($cpt->examiner);
        }

        if ($cpt->local_id && $cpt->local_id !== $unassignedUser->id) {
            $notifyUsers->push($cpt->local);
        }

        $creator = User::where('id', $cpt->created_by ?? null)->first();
        if ($creator && $creator->id !== $unassignedUser->id) {
            $notifyUsers->push($creator);
        }

        $notifyUsers = $notifyUsers->unique('id');

        $roleText = $role === 'examiner' ? 'examiner' : 'local contact';
        $message = "{$unassignedUser->full_name} has unassigned themselves as {$roleText} from the CPT for {$cpt->trainee->full_name} at {$cpt->course->solo_station} on {$cpt->date->format('d.m.Y at H:i')}lcl.";

        foreach ($notifyUsers as $user) {
            if ($user->vatsim_id) {
                $this->vatgerApi->sendNotification(
                    $user->vatsim_id,
                    'CPT Assignment Changed',
                    $message,
                    'View CPT',
                    route('cpt.index')
                );
            }
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $user->load('examiner.user');

        $cptsQuery = Cpt::with(['trainee', 'examiner.examiner', 'local', 'course.mentors'])
            ->pending()
            ->orderBy('date');

        $cpts = $cptsQuery->get()->map(function ($cpt) use ($user) {
            return [
                'id' => $cpt->id,
                'trainee' => [
                    'id' => $cpt->trainee->id,
                    'name' => $cpt->trainee->full_name,
                    'vatsim_id' => $cpt->trainee->vatsim_id,
                ],
                'examiner' => $cpt->examiner ? [
                    'id' => $cpt->examiner->id,
                    'name' => $cpt->examiner->full_name,
                    'is_current_user' => $cpt->examiner_id === $user->id,
                ] : null,
                'local' => $cpt->local ? [
                    'id' => $cpt->local->id,
                    'name' => $cpt->local->full_name,
                    'is_current_user' => $cpt->local_id === $user->id,
                ] : null,
                'course' => [
                    'id' => $cpt->course->id,
                    'name' => $cpt->course->name,
                    'solo_station' => $cpt->course->solo_station,
                    'position' => $cpt->course->position,
                ],
                'date' => $cpt->date->toIso8601String(),
                'date_formatted' => $cpt->date->format('d M Y'),
                'time_formatted' => $cpt->date->format('H:i'),
                'confirmed' => $cpt->confirmed,
                'log_uploaded' => $cpt->log_uploaded,
                'can_delete' => $user->isSuperuser() || $cpt->course->mentors->contains($user->id),
                'can_view_upload' => $user->isSuperuser() || $user->isLeadership() || $cpt->examiner_id === $user->id || $cpt->local_id === $user->id,
                'can_upload' => $user->isSuperuser() || $cpt->examiner_id === $user->id || $cpt->local_id === $user->id,
                'can_join_examiner' => $this->canJoinAsExaminer($user, $cpt),
                'can_join_local' => $this->canJoinAsLocal($user, $cpt),
            ];
        });

        $statistics = [
            'total_cpts' => $cpts->count(),
            'confirmed_cpts' => $cpts->where('confirmed', true)->count(),
            'pending_cpts' => $cpts->where('confirmed', false)->count(),
        ];

        return Inertia::render('cpt/management', [
            'cpts' => $cpts,
            'statistics' => $statistics,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        $courses = $user->isSuperuser() 
            ? Course::where('type', 'RTG')->orderBy('name')->get()
            : $user->mentorCourses()->where('type', 'RTG')->orderBy('name')->get();

        return Inertia::render('cpt/create', [
            'courses' => $courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'solo_station' => $course->solo_station,
                    'position' => $course->position,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'trainee_id' => 'required|exists:users,id',
            'date' => 'required|date|after:now',
            'examiner_id' => 'nullable|exists:users,id',
            'local_id' => 'nullable|exists:users,id',
        ]);

        $course = Course::findOrFail($validated['course_id']);

        if ($validated['trainee_id'] === ($validated['examiner_id'] ?? null)) {
            return back()->withErrors(['examiner_id' => 'Trainee cannot be the same as examiner.']);
        }

        if ($validated['trainee_id'] === ($validated['local_id'] ?? null)) {
            return back()->withErrors(['local_id' => 'Trainee cannot be the same as local contact.']);
        }

        if (isset($validated['examiner_id']) && isset($validated['local_id']) 
            && $validated['examiner_id'] === $validated['local_id']) {
            return back()->withErrors(['local_id' => 'Examiner cannot be the same as local contact.']);
        }

        if (isset($validated['examiner_id'])) {
            $examinerUser = User::with('examiner')->find($validated['examiner_id']);
            
            if (!$examinerUser || !$examinerUser->examiner) {
                return back()->withErrors(['examiner_id' => 'Selected user is not an examiner.']);
            }
            
            if (!$examinerUser->examiner->hasPosition($course->position)) {
                return back()->withErrors(['examiner_id' => 'Selected examiner is not authorized for this position.']);
            }

            $date = Carbon::parse($validated['date']);
            if ($course->mentors->contains($examinerUser->id) && $date->diffInHours(now()) > 36) {
                return back()->withErrors(['examiner_id' => 'Course mentors cannot be examiners more than 36 hours in advance.']);
            }
        }

        if (isset($validated['local_id'])) {
            if (!$course->mentors->contains($validated['local_id'])) {
                return back()->withErrors(['local_id' => 'Selected user is not a mentor for this course.']);
            }
        }

        $wasConfirmed = false;
        $isNowConfirmed = isset($validated['examiner_id']) && isset($validated['local_id']);

        $cpt = Cpt::create($validated);

        if (!$wasConfirmed && $isNowConfirmed) {
            $this->sendConfirmedCpts();
        }

        $this->notifyAvailableCpt($cpt);

        ActivityLogger::cptCreated(
            $cpt,
            $course,
            User::find($validated['trainee_id']),
            $request->user(),
            isset($validated['examiner_id']) ? User::find($validated['examiner_id']) : null,
            isset($validated['local_id']) ? User::find($validated['local_id']) : null
        );

        return redirect()->route('cpt.index')->with('success', 'CPT created successfully.');
    }

    public function getCourseData(Request $request)
    {
        $courseId = $request->get('course_id');
        $date = $request->get('date');

        if (!$courseId) {
            return response()->json(['examiners' => [], 'mentors' => [], 'trainees' => []]);
        }

        $course = Course::with(['activeTrainees', 'mentors'])->findOrFail($courseId);
        $date = Carbon::parse($date);

        $examinersQuery = Examiner::with('user')
            ->whereJsonContains('positions', $course->position);

        if ($date->diffInHours(now()) > 36) {
            $courseMentorIds = $course->mentors->pluck('id')->toArray();
            $examinersQuery->whereNotIn('user_id', $courseMentorIds);
        }

        $examiners = $examinersQuery->get()->map(function ($examiner) {
            return [
                'id' => $examiner->user->id,
                'name' => $examiner->full_display,
            ];
        });

        $mentors = $course->mentors->map(function ($mentor) {
            return [
                'id' => $mentor->id,
                'name' => $mentor->full_name,
            ];
        })->values();

        $trainees = $course->activeTrainees->map(function ($trainee) {
            return [
                'id' => $trainee->id,
                'name' => $trainee->full_name,
                'vatsim_id' => $trainee->vatsim_id,
            ];
        })->values();

        return response()->json([
            'examiners' => $examiners,
            'mentors' => $mentors,
            'trainees' => $trainees,
        ]);
    }

    public function joinExaminer(Request $request, Cpt $cpt)
    {
        $user = $request->user();
        $user->load('examiner');
        $cpt->load('course.mentors');

        if ($cpt->examiner_id === $user->id) {
            return back()->withErrors(['error' => 'You are already assigned as examiner for this CPT.']);
        }

        if ($cpt->local_id === $user->id) {
            return back()->withErrors(['error' => 'You cannot be both examiner and local contact.']);
        }

        if (!$user->examiner) {
            return back()->withErrors(['error' => 'You must have an examiner profile to join as examiner.']);
        }

        if (!$user->examiner->hasPosition($cpt->course->position)) {
            return back()->withErrors(['error' => 'You are not authorized to examine this position (' . $cpt->course->position . ').']);
        }

        if ($cpt->course->mentors->contains($user->id)) {
            if ($cpt->date->diffInHours(now()) > 36) {
                return back()->withErrors(['error' => 'Course mentors cannot be examiners more than 36 hours in advance.']);
            }
        }

        $wasConfirmed = $cpt->confirmed;
        $cpt->update(['examiner_id' => $user->id]);
        $cpt->refresh();

        if (!$wasConfirmed && $cpt->confirmed) {
            $this->sendConfirmedCpts();
        }

        ActivityLogger::cptExaminerJoined(
            $cpt,
            $cpt->course,
            $cpt->trainee,
            $user
        );

        return back()->with('success', 'Successfully joined as examiner.');
    }

    public function leaveExaminer(Request $request, Cpt $cpt)
    {
        $user = $request->user();
        $cpt->load('course', 'trainee');

        if ($cpt->examiner_id !== $user->id) {
            return back()->withErrors(['error' => 'You are not the examiner for this CPT.']);
        }

        $wasConfirmed = $cpt->confirmed;
        $cpt->update(['examiner_id' => null]);
        $cpt->refresh();

        if ($wasConfirmed && !$cpt->confirmed) {
            $this->sendConfirmedCpts();
        }

        $this->notifyUnassignment($cpt, 'examiner', $user);

        ActivityLogger::cptExaminerLeft(
            $cpt,
            $cpt->course,
            $cpt->trainee,
            $user
        );

        return back()->with('success', 'Successfully left as examiner.');
    }

    public function joinLocal(Request $request, Cpt $cpt)
    {
        $user = $request->user();
        $cpt->load('course.mentors', 'trainee');

        if ($cpt->local_id === $user->id) {
            return back()->withErrors(['error' => 'You are already assigned as local contact for this CPT.']);
        }

        if ($cpt->examiner_id === $user->id) {
            return back()->withErrors(['error' => 'You cannot be both examiner and local contact.']);
        }

        if (!$cpt->course->mentors->contains($user->id)) {
            return back()->withErrors(['error' => 'You must be a mentor of this course to join as local contact.']);
        }

        $wasConfirmed = $cpt->confirmed;
        $cpt->update(['local_id' => $user->id]);
        $cpt->refresh();

        if (!$wasConfirmed && $cpt->confirmed) {
            $this->sendConfirmedCpts();
        }

        ActivityLogger::cptLocalJoined(
            $cpt,
            $cpt->course,
            $cpt->trainee,
            $user
        );

        return back()->with('success', 'Successfully joined as local contact.');
    }

    public function leaveLocal(Request $request, Cpt $cpt)
    {
        $user = $request->user();
        $cpt->load('course', 'trainee');

        if ($cpt->local_id !== $user->id) {
            return back()->withErrors(['error' => 'You are not the local contact for this CPT.']);
        }

        $wasConfirmed = $cpt->confirmed;
        $cpt->update(['local_id' => null]);
        $cpt->refresh();

        if ($wasConfirmed && !$cpt->confirmed) {
            $this->sendConfirmedCpts();
        }

        $this->notifyUnassignment($cpt, 'local', $user);

        ActivityLogger::cptLocalLeft(
            $cpt,
            $cpt->course,
            $cpt->trainee,
            $user
        );

        return back()->with('success', 'Successfully left as local contact.');
    }

    public function destroy(Request $request, Cpt $cpt)
    {
        $user = $request->user();
        $cpt->load('course.mentors', 'trainee');

        if (!$user->isSuperuser() && !$cpt->course->mentors->contains($user->id)) {
            return back()->withErrors(['error' => 'You do not have permission to delete this CPT.']);
        }

        if ($cpt->passed !== null) {
            return back()->withErrors(['error' => 'Cannot delete a graded CPT.']);
        }

        $wasConfirmed = $cpt->confirmed;

        ActivityLogger::cptDeleted(
            $cpt,
            $cpt->course,
            $cpt->trainee,
            $user
        );

        $cpt->delete();

        if ($wasConfirmed) {
            $this->sendConfirmedCpts();
        }

        return back()->with('success', 'CPT deleted successfully.');
    }

    public function uploadPage(Cpt $cpt)
    {
        $user = auth()->user();
        $cpt->load(['trainee', 'examiner', 'local', 'course', 'logs.uploadedBy']);

        $canAccess = $user->isSuperuser() 
            || $user->isLeadership()
            || $cpt->examiner_id === $user->id 
            || $cpt->local_id === $user->id;

        if (!$canAccess) {
            return redirect()->route('cpt.index')->withErrors(['error' => 'You do not have permission to view logs for this CPT.']);
        }

        $canUpload = $user->isSuperuser() 
            || $cpt->examiner_id === $user->id 
            || $cpt->local_id === $user->id;

        $canReview = $user->isSuperuser() && $cpt->log_uploaded;

        return Inertia::render('cpt/upload', [
            'cpt' => [
                'id' => $cpt->id,
                'trainee' => [
                    'id' => $cpt->trainee->id,
                    'name' => $cpt->trainee->full_name,
                    'vatsim_id' => $cpt->trainee->vatsim_id,
                ],
                'examiner' => $cpt->examiner ? [
                    'id' => $cpt->examiner->id,
                    'name' => $cpt->examiner->full_name,
                ] : null,
                'local' => $cpt->local ? [
                    'id' => $cpt->local->id,
                    'name' => $cpt->local->full_name,
                ] : null,
                'course' => [
                    'id' => $cpt->course->id,
                    'name' => $cpt->course->name,
                    'solo_station' => $cpt->course->solo_station,
                ],
                'date' => $cpt->date->toIso8601String(),
                'date_formatted' => $cpt->date->format('d M Y, H:i'),
                'confirmed' => $cpt->confirmed,
                'log_uploaded' => $cpt->log_uploaded,
            ],
            'logs' => $cpt->logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'file_name' => $log->file_name,
                    'file_url' => $log->file_url,
                    'uploaded_at' => $log->created_at->toIso8601String(),
                    'uploaded_at_formatted' => $log->created_at->format('d M Y, H:i'),
                    'uploaded_by' => [
                        'id' => $log->uploadedBy->id,
                        'name' => $log->uploadedBy->full_name,
                    ],
                ];
            }),
            'can_upload' => $canUpload,
            'can_review' => $canReview,
        ]);
    }

    public function upload(Request $request, Cpt $cpt)
    {
        $validated = $request->validate([
            'log_file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $user = $request->user();

        if (!$user->isSuperuser() && $cpt->examiner_id !== $user->id && $cpt->local_id !== $user->id) {
            return back()->withErrors(['error' => 'You do not have permission to upload logs for this CPT.']);
        }

        $path = $request->file('log_file')->store('cpt_logs', 'public');

        $cptLog = CptLog::create([
            'cpt_id' => $cpt->id,
            'uploaded_by_id' => $user->id,
            'log_file' => $path,
        ]);

        $cpt->update(['log_uploaded' => true]);

        $cpt->load('course', 'trainee');
        ActivityLogger::cptLogUploaded(
            $cptLog,
            $cpt,
            $cpt->course,
            $cpt->trainee,
            $user
        );

        return back()->with('success', 'Log uploaded successfully.');
    }

    public function grade(Request $request, Cpt $cpt, int $result)
    {
        $user = $request->user();

        if (!$user->isSuperuser()) {
            return back()->withErrors(['error' => 'Only ATD can grade CPTs.']);
        }

        if ($result !== 0 && $result !== 1) {
            return back()->withErrors(['error' => 'Invalid grading option.']);
        }

        $passed = $result === 1;
        $cpt->update(['passed' => $passed]);

        $cpt->load('course', 'trainee', 'examiner', 'logs');

        if ($cpt->log_uploaded && $cpt->logs->count() > 0) {
            $latestLog = $cpt->logs->sortByDesc('created_at')->first();

            $filePath = storage_path('app/' . $latestLog->log_file);
            if (!file_exists($filePath)) {
                $filePath = storage_path('app/public/' . $latestLog->log_file);
            }

            if (file_exists($filePath) && $cpt->examiner) {
                $uploadResult = $this->vatEudService->uploadCptLog(
                    $cpt->trainee->vatsim_id,
                    $cpt->examiner->vatsim_id,
                    $cpt->course->solo_station,
                    'See log',
                    $passed,
                    $filePath
                );

                if ($uploadResult['success'] && $passed) {
                    $this->vatEudService->requestUpgrade(
                        $cpt->trainee->vatsim_id,
                        $user->vatsim_id,
                        $cpt->trainee->rating + 1
                    );
                }
            }
        }

        ActivityLogger::cptGraded(
            $cpt,
            $cpt->course,
            $cpt->trainee,
            $user,
            $passed
        );

        return back()->with('success', 'CPT graded successfully.');
    }

    public function viewLog(CptLog $log)
    {
        $user = auth()->user();
        $cpt = $log->cpt()->with('course.mentors')->first();

        $canView = $user->isSuperuser() 
            || $user->isLeadership()
            || $cpt->examiner_id === $user->id 
            || $cpt->local_id === $user->id;

        if (!$canView) {
            abort(403, 'Unauthorized access to CPT log.');
        }

        $filePath = storage_path('app/' . $log->log_file);
        
        if (!file_exists($filePath)) {
            $filePath = storage_path('app/public/' . $log->log_file);
        }

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $log->file_name . '"'
        ]);
    }

    private function canJoinAsExaminer(User $user, Cpt $cpt): bool
    {
        if ($cpt->examiner_id === $user->id) {
            return false;
        }

        if ($cpt->local_id === $user->id) {
            return false;
        }

        if (!$user->relationLoaded('examiner')) {
            $user->load('examiner');
        }

        if (!$user->examiner) {
            return false;
        }

        if (!$user->examiner->hasPosition($cpt->course->position)) {
            return false;
        }

        if (!$cpt->course->relationLoaded('mentors')) {
            $cpt->course->load('mentors');
        }

        if ($cpt->course->mentors->contains($user->id)) {
            if ($cpt->date->diffInHours(now()) > 36) {
                return false;
            }
        }

        return true;
    }

    private function canJoinAsLocal(User $user, Cpt $cpt): bool
    {
        if ($cpt->local_id === $user->id) {
            return false;
        }

        if ($cpt->examiner_id === $user->id) {
            return false;
        }

        if (!$cpt->course->relationLoaded('mentors')) {
            $cpt->course->load('mentors');
        }

        if (!$cpt->course->mentors->contains($user->id)) {
            return false;
        }

        return true;
    }
}